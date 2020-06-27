<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;
use App\Jobs\DeployPubQr;
use App\Deployment;
use App\DeploymentProviders\DigitalOceanProvider;
use Ramsey\Uuid\Uuid;
use App\Exceptions\DeploymentException;
use App\DeploymentFailure;
use Illuminate\Support\Facades\Mail;
use App\Mail\DeploymentFinished;
use App\Mail\DeploymentFailed;

class DeploymentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function anyone_can_see_the_deployment_page()
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeLivewire('deployment-form');
    }

    /** @test */
    public function users_can_fill_in_the_deployment_form_and_kick_off_a_deployment()
    {
        Queue::fake();

        Livewire::test('deployment-form')
            ->assertSee('Deploy PubQR')
            ->set('apiKey', 'abcd1234')
            ->set('email', 'jenny@example.com')
            ->set('url', 'https://mypub.example.com')
            ->set('shopName', 'My Pub')
            ->call('deploy')
            ->assertHasNoErrors();

        Queue::assertPushed(DeployPubQr::class);
    }

    /** @test */
    public function users_must_put_in_valid_data_before_they_kick_off_a_deployment()
    {
        Queue::fake();

        Livewire::test('deployment-form')
            ->assertSee('Deploy PubQR')
            ->set('apiKey', '')
            ->set('email', 'jenny')
            ->set('url', '123131')
            ->set('shopName', '')
            ->call('deploy')
            ->assertHasErrors('apiKey', 'email', 'url', 'shopName');

        Queue::assertNotPushed(DeployPubQr::class);
    }

    /** @test */
    public function starting_a_deployment_job_stores_an_encrypted_record_in_the_database()
    {
        $this->mock(DigitalOceanProvider::class, function ($mock) {
            $mock->shouldReceive('deploy');
        });

        DeployPubQr::dispatch('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop');

        tap(Deployment::first(), function ($deployment) {
            $this->assertEquals('abcd1234', decrypt($deployment->api_key));
            $this->assertEquals('jenny@example.com', decrypt($deployment->email));
            $this->assertEquals('https://shop.example.com', decrypt($deployment->url));
            $this->assertEquals('My Shop', decrypt($deployment->shop_name));
        });
    }

    /** @test */
    public function the_deployment_records_can_be_removed_after_a_given_time()
    {
        config(['pubqr.record_retention_days' => 14]);
        factory(Deployment::class)->create(['updated_at' => now()->subDays(20)]);
        factory(Deployment::class)->create(['updated_at' => now()->subDays(15)]);
        factory(Deployment::class)->create(['updated_at' => now()->subDays(10)]);
        factory(Deployment::class)->create(['updated_at' => now()->subDays(5)]);

        $this->assertEquals(4, Deployment::count());

        $this->artisan('pubqr:remove-old-records');

        $this->assertEquals(2, Deployment::count());
    }

    /** @test */
    public function record_removal_is_registered_with_the_schedular()
    {
        $this->assertCommandIsScheduled('pubqr:remove-old-records');
    }

    /** @test */
    public function a_deployment_job_calls_the_digital_ocean_factory_with_the_correct_parameters()
    {
        $this->mock(DigitalOceanProvider::class, function ($mock) {
            $mock->shouldReceive('deploy')
                ->with('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop')
                ->once()
                ->andReturn('192.168.1.33');
        });

        DeployPubQr::dispatch('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop');
    }

    /** @test */
    public function if_the_deployment_succeeds_we_update_the_deployment_record_with_a_successful_status()
    {
        $this->mock(DigitalOceanProvider::class, function ($mock) {
            $mock->shouldReceive('deploy')
                ->with('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop')
                ->once()
                ->andReturn('192.168.1.33');
        });

        DeployPubQr::dispatch('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop');

        tap(Deployment::first(), function ($deployment) {
            $this->assertEquals(Deployment::STATUS_SUCCEEDED, $deployment->status);
        });
    }

    /** @test */
    public function when_a_deployment_finished_successfully_we_email_the_user_with_the_details()
    {
        Mail::fake();

        $this->mock(DigitalOceanProvider::class, function ($mock) {
            $mock->shouldReceive('deploy')
                ->with('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop')
                ->once()
                ->andReturn('192.168.1.33');
        });

        DeployPubQr::dispatch('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop');

        Mail::assertQueued(DeploymentFinished::class, function ($mail) {
            return $mail->hasTo('jenny@example.com') && $mail->uuid === Deployment::first()->uuid;
        });
    }

    /** @test */
    public function if_the_provider_throws_an_exception_we_catch_it_and_record_an_error()
    {
        $this->mock(DigitalOceanProvider::class, function ($mock) {
            $mock->shouldReceive('deploy')
                ->with('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop')
                ->once()
                ->andThrow(new DeploymentException(Uuid::uuid4()));
        });

        DeployPubQr::dispatch('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop');

        tap(DeploymentFailure::first(), function ($failure) {
            $record = Deployment::first();
            $this->assertEquals($record->uuid, $failure->uuid);
            $this->assertNotNull($failure->stack_trace);
            $this->assertEquals(Deployment::STATUS_FAILED, $record->status);
        });
    }

    /** @test */
    public function if_the_provider_throws_an_exception_we_email_the_user_with_the_details()
    {
        Mail::fake();

        $this->mock(DigitalOceanProvider::class, function ($mock) {
            $mock->shouldReceive('deploy')
                ->with('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop')
                ->once()
                ->andThrow(new DeploymentException(Uuid::uuid4()));
        });

        DeployPubQr::dispatch('abcd1234', 'jenny@example.com', 'https://shop.example.com', 'My Shop');

        Mail::assertQueued(DeploymentFailed::class, function ($mail) {
            return $mail->hasTo('jenny@example.com') && $mail->uuid === Deployment::first()->uuid;
        });
    }

    /** @test */
    public function the_digital_ocean_provider_calls_out_to_actually_create_a_droplet()
    {
        $this->markTestSkipped('TODO');
    }
}
