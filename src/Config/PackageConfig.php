<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Config;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Arr;
use RuntimeException;
use UnexpectedValueException;
use Wimski\LaravelNominatim\Enums\ServiceEnum;
use Wimski\Nominatim\Config\LocationIqConfig;
use Wimski\Nominatim\Config\NominatimConfig;
use Wimski\Nominatim\Contracts\Config\ConfigInterface;

class PackageConfig
{
    protected ServiceEnum $service;
    protected ?string $language;
    protected ConfigInterface $serviceConfig;

    /**
     * @param Config $config
     * @throws RuntimeException
     */
    public function __construct(
        protected Config $config,
    ) {
        $data = $this->getConfigData();

        $this->service       = $this->getServiceData($data);
        $this->language      = $this->getLanguageData($data);
        $this->serviceConfig = $this->getServiceConfigData($data);
    }

    public function getService(): ServiceEnum
    {
        return $this->service;
    }

    public function getServiceConfig(): ConfigInterface
    {
        return $this->serviceConfig;
    }

    /**
     * @return array<string, mixed>
     * @throws RuntimeException
     */
    protected function getConfigData(): array
    {
        /** @var array<string, mixed>|null $data */
        $data = $this->config->get('nominatim');

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
     * @param array<string, mixed> $data
     * @return ConfigInterface
     * @throws RuntimeException
     */
    protected function getServiceConfigData(array $data): ConfigInterface
    {
        $service = $this->service->getValue();

        /** @var array<string, array<string, mixed>> $services */
        $services = $data['services'];

        if (! array_key_exists($service, $services)) {
            throw new RuntimeException("The config value 'nominatim.services.{$service}' must be present");
        }

        return match ($service) {
            ServiceEnum::NOMINATIM   => $this->makeNominatimConfig($services[$service]),
            ServiceEnum::LOCATION_IQ => $this->makeLocationIqConfig($services[$service]),
            default                  => throw new RuntimeException('Unreachable statement'),
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return NominatimConfig
     * @throws RuntimeException
     */
    protected function makeNominatimConfig(array $data): NominatimConfig
    {
        return new NominatimConfig(
            $this->getArrayStringValue($data, 'user_agent'),
            $this->getArrayStringValue($data, 'email'),
            $this->getArrayStringValue($data, 'url'),
            $this->getArrayStringValue($data, 'forward_geocoding_endpoint'),
            $this->getArrayStringValue($data, 'reverse_geocoding_endpoint'),
            $this->language,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return LocationIqConfig
     * @throws RuntimeException
     */
    protected function makeLocationIqConfig(array $data): LocationIqConfig
    {
        return new LocationIqConfig(
            $this->getArrayStringValue($data, 'key'),
            $this->getArrayStringValue($data, 'url'),
            $this->getArrayStringValue($data, 'forward_geocoding_endpoint'),
            $this->getArrayStringValue($data, 'reverse_geocoding_endpoint'),
            $this->language,
        );
    }

    /**
     * @param array<string, mixed> $array
     * @param string               $key
     * @return string
     * @throws RuntimeException
     */
    protected function getArrayStringValue(array $array, string $key): string
    {
        $value = Arr::get($array, $key);

        if (is_string($value)) {
            return $value;
        }

        throw new RuntimeException("The config value 'nominatim.services.{$this->service->getValue()}.{$key}' must be a string");
    }
}
