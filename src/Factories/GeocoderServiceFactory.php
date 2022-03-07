<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Factories;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Arr;
use RuntimeException;
use UnexpectedValueException;
use Wimski\LaravelNominatim\Enums\ServiceEnum;
use Wimski\Nominatim\Config\GenericConfig;
use Wimski\Nominatim\Config\LocationIqConfig;
use Wimski\Nominatim\Config\NominatimConfig;
use Wimski\Nominatim\Contracts\ClientInterface;
use Wimski\Nominatim\Contracts\GeocoderServiceInterface;
use Wimski\Nominatim\Contracts\Transformers\GeocodingResponseTransformerInterface;
use Wimski\Nominatim\GeocoderServices\GenericGeocoderService;
use Wimski\Nominatim\GeocoderServices\LocationIqGeocoderService;
use Wimski\Nominatim\GeocoderServices\NominatimGeocoderService;

class GeocoderServiceFactory
{
    public function __construct(
        protected ClientInterface $client,
        protected GeocodingResponseTransformerInterface $geocodingResponseTransformer,
    ) {
    }

    /**
     * @param Config $config
     * @return GeocoderServiceInterface
     * @throws RuntimeException
     */
    public function make(Config $config): GeocoderServiceInterface
    {
        $data     = $this->getConfigData($config);
        $service  = $this->getServiceData($data);
        $language = $this->getLanguageData($data);

        $serviceValue = $service->getValue();

        /** @var array<string, array<string, mixed>> $services */
        $services = $data['services'];

        if (! array_key_exists($serviceValue, $services)) {
            throw new RuntimeException("The config value 'nominatim.services.{$serviceValue}' must be present");
        }

        /** @var array<string, mixed> $serviceConfig */
        $serviceConfig = $services[$serviceValue];

        return match ($serviceValue) {
            ServiceEnum::NOMINATIM   => $this->makeNominatimService($service, $language, $serviceConfig),
            ServiceEnum::LOCATION_IQ => $this->makeLocationIqService($service, $language, $serviceConfig),
            ServiceEnum::GENERIC     => $this->makeGenericService($service, $language, $serviceConfig),
            default                  => throw new RuntimeException('Unreachable statement'),
        };
    }

    /**
     * @param ServiceEnum          $service
     * @param string|null          $language
     * @param array<string, mixed> $data
     * @return NominatimGeocoderService
     * @throws RuntimeException
     */
    protected function makeNominatimService(ServiceEnum $service, ?string $language, array $data): NominatimGeocoderService
    {
        $config = new NominatimConfig(
            $this->getServiceConfigValue($service, $data, 'user_agent'),
            $this->getServiceConfigValue($service, $data, 'email'),
            $this->getServiceConfigValue($service, $data, 'url'),
            $this->getServiceConfigValue($service, $data, 'forward_geocoding_endpoint'),
            $this->getServiceConfigValue($service, $data, 'reverse_geocoding_endpoint'),
            $language,
        );

        return new NominatimGeocoderService(
            $this->client,
            $this->geocodingResponseTransformer,
            $config,
        );
    }

    /**
     * @param ServiceEnum          $service
     * @param string|null          $language
     * @param array<string, mixed> $data
     * @return LocationIqGeocoderService
     * @throws RuntimeException
     */
    protected function makeLocationIqService(ServiceEnum $service, ?string $language, array $data): LocationIqGeocoderService
    {
        $config = new LocationIqConfig(
            $this->getServiceConfigValue($service, $data, 'key'),
            $this->getServiceConfigValue($service, $data, 'url'),
            $this->getServiceConfigValue($service, $data, 'forward_geocoding_endpoint'),
            $this->getServiceConfigValue($service, $data, 'reverse_geocoding_endpoint'),
            $language,
        );

        return new LocationIqGeocoderService(
            $this->client,
            $this->geocodingResponseTransformer,
            $config,
        );
    }

    /**
     * @param ServiceEnum          $service
     * @param string|null          $language
     * @param array<string, mixed> $data
     * @return GenericGeocoderService
     * @throws RuntimeException
     */
    protected function makeGenericService(ServiceEnum $service, ?string $language, array $data): GenericGeocoderService
    {
        $config = new GenericConfig(
            $this->getServiceConfigValue($service, $data, 'url'),
            $this->getServiceConfigValue($service, $data, 'forward_geocoding_endpoint'),
            $this->getServiceConfigValue($service, $data, 'reverse_geocoding_endpoint'),
            $language,
        );

        return new GenericGeocoderService(
            $this->client,
            $this->geocodingResponseTransformer,
            $config,
        );
    }

    /**
     * @param Config $config
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    protected function getConfigData(Config $config): array
    {
        /** @var array<string, mixed>|null $data */
        $data = $config->get('nominatim');

        if ($data) {
            return $data;
        }

        throw new RuntimeException('Nominatim config not found');
    }

    /**
     * @param array<string, mixed> $data
     * @return ServiceEnum
     * @throws RuntimeException
     */
    protected function getServiceData(array $data): ServiceEnum
    {
        try {
            return ServiceEnum::from(Arr::get($data, 'service'));
        } catch (UnexpectedValueException $exception) {
            throw new RuntimeException("The config value 'nominatim.service' is not supported");
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return string|null
     * @throws RuntimeException
     */
    protected function getLanguageData(array $data): ?string
    {
        $language = Arr::get($data, 'language');

        if (is_string($language) || $language === null) {
            return $language;
        }

        throw new RuntimeException("The config value 'nominatim.language' must be a string or null");
    }

    /**
     * @param ServiceEnum          $service
     * @param array<string, mixed> $config
     * @param string               $key
     * @return string
     * @throws RuntimeException
     */
    protected function getServiceConfigValue(ServiceEnum $service, array $config, string $key): string
    {
        $value = Arr::get($config, $key);

        if (is_string($value)) {
            return $value;
        }

        throw new RuntimeException("The config value 'nominatim.services.{$service->getValue()}.{$key}' must be a string");
    }
}
