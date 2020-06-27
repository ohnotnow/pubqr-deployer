<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeploymentFinished extends Mailable
{
    use Queueable, SerializesModels;

    public $ip;
    public $shopName;
    public $url;
    public $uuid;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ip, $shopName, $url, $uuid)
    {
        $this->ip = $ip;
        $this->shopName = $shopName;
        $this->url = $url;
        $this->uuid = $uuid;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.deployment_finished');
    }
}
