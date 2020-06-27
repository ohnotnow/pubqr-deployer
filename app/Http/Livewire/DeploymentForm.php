<?php

namespace App\Http\Livewire;

use App\Jobs\DeployPubQr;
use Livewire\Component;

class DeploymentForm extends Component
{
    public $apiKey;
    public $email;
    public $url;
    public $shopName;

    public function render()
    {
        return view('livewire.deployment-form');
    }

    public function deploy()
    {
        $this->validate([
            'apiKey' => 'required|min:8',
            'email' => 'required|email',
            'url' => 'required|url',
            'shopName' => 'required',
        ]);

        DeployPubQr::dispatch($this->apiKey, $this->email, $this->url, $this->shopName);
    }
}
