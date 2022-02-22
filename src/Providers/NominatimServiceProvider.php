<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Providers;

use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use RuntimeException;
use Wimski\LaravelNominatim\Config\PackageConfig;
use Wimski\LaravelNominatim\Enums\ServiceEnum;
use Wimski\Nominatim\Client;
use Wimski\Nominatim\Config\LocationIqConfig;
use Wimski\Nominatim\Config\NominatimConfig;
use Wimski\Nominatim\Contracts\ClientInterface;
use Wimski\Nominatim\Contracts\GeocoderServiceInterface;
use Wimski\Nominatim\Contracts\Transformers\GeocodingResponseTransformerInterface;
use Wimski\Nominatim\GeocoderServices\LocationIqGeocoderService;
use Wimski\Nominatim\GeocoderServices\NominatimGeocoderService;
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
            /** @var Config $config */
            $config = $app->make(Config::class);

            $packageConfig = new PackageConfig($config);

            return match ($packageConfig->getService()->getValue()) {
                ServiceEnum::NOMINATIM   => $this->makeNominatimService($packageConfig),
                ServiceEnum::LOCATION_IQ => $this->makeLocationIqService($packageConfig),
                default                  => throw new RuntimeException('Unreachable statement'),
            };
        });
    }

    public function boot(): void
    {
        $this->publishes([
            $this->getConfigPath() => config_path('nominatim.php'),
        ]);
    }

    /**
     * @param PackageConfig $config
     * @return NominatimGeocoderService
     * @throws BindingResolutionException
     */
    protected function makeNominatimService(PackageConfig $config): NominatimGeocoderService
    {
        /** @var NominatimConfig $serviceConfig */
        $serviceConfig = $config->getServiceConfig();

        return new NominatimGeocoderService(
            $this->makeClientInterface(),
            $this->makeGeocodingResponseTransformerInterface(),
            $serviceConfig,
        );
    }

    /**
     * @param PackageConfig $config
     * @return LocationIqGeocoderService
     * @throws BindingResolutionException
     */
    protected function makeLocationIqService(PackageConfig $config): LocationIqGeocoderService
    {
        /** @var LocationIqConfig $serviceConfig */
        $serviceConfig = $config->getServiceConfig();

        return new LocationIqGeocoderService(
            $this->makeClientInterface(),
            $this->makeGeocodingResponseTransformerInterface(),
            $serviceConfig,
        );
    }

    /**
     * @return ClientInterface
     * @throws BindingResolutionException
     */
    protected function makeClientInterface(): ClientInterface
    {
        /** @var ClientInterface $client */
        $client = $this->app->make(ClientInterface::class);

        return $client;
    }

    /**
     * @return GeocodingResponseTransformerInterface
     * @throws BindingResolutionException
     */
    protected function makeGeocodingResponseTransformerInterface(): GeocodingResponseTransformerInterface
    {
        /** @var GeocodingResponseTransformerInterface $transformer */
        $transformer = $this->app->make(GeocodingResponseTransformerInterface::class);

        return $transformer;
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
