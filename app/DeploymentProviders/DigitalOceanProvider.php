<?php

namespace App\DeploymentProviders;

use App\Exceptions\DeploymentException;
use DigitalOceanV2\Adapter\BuzzAdapter;
use DigitalOceanV2\DigitalOceanV2;

class DigitalOceanProvider implements ProviderContract
{
    protected $adaptor;
    protected $digitalOcean;

    public function deploy(string $apiKey, string $email, string $url, string $shopName)
    {
        try {
            $this->adapter = new BuzzAdapter($apiKey);
            $this->digitalOcean = new DigitalOceanV2($this->adapter);

            $dropletApi = $this->digitalOcean->droplet();
            $actualDroplet = $dropletApi->create($shopName, 'lon1', '1024mb', 'ubuntu-20-04-x64');
            return $actualDroplet['networks']['v4'][0]['ip_address'];
        } catch (\Exception $e) {
            info('Exception during deployment of digital ocean droplet');
            info($e->getMessage());
            info($e->getTraceAsString());
            throw new DeploymentException($e->getMessage());
        }

        // we shouldn't get here - so...
        throw new DeploymentException('Unexpectedly hit the end');
    }
}
