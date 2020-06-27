<?php

namespace App\DeploymentProviders;

interface ProviderContract
{
    public function deploy(string $apiKey, string $email, string $url, string $shopName);
}
