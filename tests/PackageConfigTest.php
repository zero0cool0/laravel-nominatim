<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Tests;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Arr;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wimski\LaravelNominatim\Config\PackageConfig;
use Wimski\LaravelNominatim\Enums\ServiceEnum;
use Wimski\Nominatim\Config\LocationIqConfig;
use Wimski\Nominatim\Config\NominatimConfig;

class PackageConfigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     */
    public function it_parses_nominatim_config(): void
    {
        $packageConfig = $this->makeConfigWithData([
            'service'  => 'nominatim',
            'language' => 'nl',
            'services' => [
                'nominatim' => [
                    'user_agent'                 => 'app-identifier',
                    'email'                      => 'email@provider.net',
                    'url'                        => 'https://api.org',
                    'forward_geocoding_endpoint' => 'front',
                    'reverse_geocoding_endpoint' => 'back',
                ],
            ],
        ]);

        static::assertTrue($packageConfig->getService()->equals(ServiceEnum::NOMINATIM()));

        $service = $packageConfig->getServiceConfig();

        static::assertInstanceOf(NominatimConfig::class, $service);
        static::assertSame('app-identifier', $service->getUserAgent());
        static::assertSame('email@provider.net', $service->getEmail());
        static::assertSame('https://api.org', $service->getUrl());
        static::assertSame('front', $service->getForwardGeocodingEndpoint());
        static::assertSame('back', $service->getReverseGeocodingEndpoint());
        static::assertSame('nl', $service->getLanguage());
    }

    /**
     * @test
     */
    public function it_parses_location_iq_config(): void
    {
        $packageConfig = $this->makeConfigWithData([
            'service'  => 'location_iq',
            'language' => 'nl',
            'services' => [
                'location_iq' => [
                    'key'                        => 'access-token',
                    'url'                        => 'https://api.org',
                    'forward_geocoding_endpoint' => 'front',
                    'reverse_geocoding_endpoint' => 'back',
                ],
            ],
        ]);

        static::assertTrue($packageConfig->getService()->equals(ServiceEnum::LOCATION_IQ()));

        $service = $packageConfig->getServiceConfig();

        static::assertInstanceOf(LocationIqConfig::class, $service);
        static::assertSame('access-token', $service->getKey());
        static::assertSame('https://api.org', $service->getUrl());
        static::assertSame('front', $service->getForwardGeocodingEndpoint());
        static::assertSame('back', $service->getReverseGeocodingEndpoint());
        static::assertSame('nl', $service->getLanguage());
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_config_is_missing(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('Nominatim config not found');

        $this->makeConfigWithData(null);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_service_is_not_supported(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage("The config value 'nominatim.service' is not supported");

        $this->makeConfigWithData([
            'service' => 'foo',
        ]);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_language_is_not_a_string_or_null(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage("The config value 'nominatim.language' must be a string or null");

        $this->makeConfigWithData([
            'service'  => 'nominatim',
            'language' => 123,
        ]);
    }

    /**
     * @test
     * @dataProvider serviceDataProvider
     */
    public function it_throws_an_exception_if_the_service_config_is_missing(string $service): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage("The config value 'nominatim.services.{$service}' must be present");

        $this->makeConfigWithData([
            'service'  => $service,
            'language' => null,
            'services' => [],
        ]);
    }

    /**
     * @return string[][]
     */
    public function serviceDataProvider(): array
    {
        return array_map(function (ServiceEnum $enum) {
            return [$enum->getValue()];
        }, ServiceEnum::values());
    }

    /**
     * @test
     * @dataProvider configValuesDataProvider
     */
    public function it_throws_an_exception_if_a_service_config_value_is_not_a_string(string $service, string $key): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage("The config value 'nominatim.services.{$service}.{$key}' must be a string");

        $data = [
            'service'  => $service,
            'language' => null,
            'services' => [
                'nominatim' => [
                    'user_agent'                 => 'x',
                    'email'                      => 'x',
                    'url'                        => 'x',
                    'forward_geocoding_endpoint' => 'x',
                    'reverse_geocoding_endpoint' => 'x',
                ],
                'location_iq' => [
                    'key'                        => 'x',
                    'url'                        => 'x',
                    'forward_geocoding_endpoint' => 'x',
                    'reverse_geocoding_endpoint' => 'x',
                ],
            ],
        ];

        Arr::forget($data, "services.{$service}.{$key}");

        $this->makeConfigWithData($data);
    }

    /**
     * @return string[][]
     */
    public function configValuesDataProvider(): array
    {
        return [
            ['nominatim', 'user_agent'],
            ['nominatim', 'email'],
            ['nominatim', 'url'],
            ['nominatim', 'forward_geocoding_endpoint'],
            ['nominatim', 'reverse_geocoding_endpoint'],
            ['location_iq', 'key'],
            ['location_iq', 'url'],
            ['location_iq', 'forward_geocoding_endpoint'],
            ['location_iq', 'reverse_geocoding_endpoint'],
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     * @return PackageConfig
     */
    protected function makeConfigWithData(?array $data): PackageConfig
    {
        /** @var Config|MockInterface $config */
        $config = Mockery::mock(Config::class)
            ->shouldReceive('get')
            ->once()
            ->with('nominatim')
            ->andReturn($data)
            ->getMock();

        return new PackageConfig($config);
    }
}
