<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Providers;

use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Wimski\LaravelNominatim\Factories\GeocoderServiceFactory;
use Wimski\Nominatim\Client;
use Wimski\Nominatim\Contracts\ClientInterface;
use Wimski\Nominatim\Contracts\GeocoderServiceInterface;
use Wimski\Nominatim\Contracts\Transformers\GeocodingResponseTransformerInterface;
use Wimski\Nominatim\Transformers\GeocodingResponseTransformer;

class NominatimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'nominatim');

        $this->app->singleton(GeocodingResponseTransformerInterface::class, GeocodingResponseTransformer::class);

        $this->app->singleton(ClientInterface::class, function (): ClientInterface {
            return new Client(
                $this->getHttpClient(),
                $this->getRequestFactory(),
                $this->getUriFactory(),
            );
        });

        $this->app->singleton(GeocoderServiceInterface::class, function (Container $app): GeocoderServiceInterface {
            /** @var ClientInterface $client */
            $client = $this->app->make(ClientInterface::class);

            /** @var GeocodingResponseTransformerInterface $transformer */
            $transformer = $this->app->make(GeocodingResponseTransformerInterface::class);

            $factory = new GeocoderServiceFactory($client, $transformer);

            /** @var Config $config */
            $config = $app->make(Config::class);

            return $factory->make($config);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            $this->getConfigPath() => config_path('nominatim.php'),
        ]);
    }

    protected function getHttpClient(): ?HttpClientInterface
    {
        return null;
    }

    protected function getRequestFactory(): ?RequestFactoryInterface
    {
        return null;
    }

    protected function getUriFactory(): ?UriFactoryInterface
    {
        return null;
    }

    protected function getConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/config/nominatim.php';
    }
}
