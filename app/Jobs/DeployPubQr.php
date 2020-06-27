<?php

namespace App\Jobs;

use App\Deployment;
use App\DeploymentProviders\DigitalOceanProvider;
use App\Exceptions\DeploymentException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\Uuid;
use App\DeploymentFailure;
use Illuminate\Support\Facades\Mail;
use App\Mail\DeploymentFinished;
use App\Mail\DeploymentFailed;

class DeployPubQr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $apiKey;
    public $email;
    public $shopName;
    public $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $apiKey, string $email, string $url, string $shopName)
    {
        $this->apiKey = $apiKey;
        $this->email = $email;
        $this->url = $url;
        $this->shopName = $shopName;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $record = Deployment::create([
            'api_key' => encrypt($this->apiKey),
            'email' => encrypt($this->email),
            'url' => encrypt($this->url),
            'shop_name' => encrypt($this->shopName),
            'uuid' => Uuid::uuid4()->toString(),
        ]);

        $ip = null;
        try {
            $ip = app(DigitalOceanProvider::class)->deploy($this->apiKey, $this->email, $this->url, $this->shopName);
        } catch (DeploymentException $e) {
            DeploymentFailure::create([
                'uuid' => $record->uuid,
                'stack_trace' => $e->getTraceAsString(),
                'message' => $e->getMessage(),
            ]);
            $record->markFailed();
            Mail::to($this->email)
                ->queue(new DeploymentFailed($this->shopName, $record->uuid));
            return false;
        }

        $record->markSucceeded();

        Mail::to($this->email)
            ->queue(new DeploymentFinished($ip, $this->shopName, $this->url, $record->uuid));

        return $ip;
    }
}
