<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Tests;

use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Swis\Http\Fixture\Client;
use Swis\Http\Fixture\ResponseBuilder;
use Wimski\LaravelNominatim\Providers\NominatimServiceProvider;

class ExtendedServiceProvider extends NominatimServiceProvider
{
    protected function getHttpClient(): ?HttpClientInterface
    {
        $responseBuilder = new ResponseBuilder(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures');

        return new Client($responseBuilder);
    }
}
