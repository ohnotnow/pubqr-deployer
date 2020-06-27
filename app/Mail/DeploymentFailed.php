<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeploymentFailed extends Mailable
{
    use Queueable, SerializesModels;

    public $shopName;
    public $uuid;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $shopName, string $uuid)
    {
        $this->shopName = $shopName;
        $this->uuid = $uuid;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.deployment_failed');
    }
}
