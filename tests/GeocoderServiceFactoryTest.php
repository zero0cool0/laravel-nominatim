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
use Wimski\LaravelNominatim\Enums\ServiceEnum;
use Wimski\LaravelNominatim\Factories\GeocoderServiceFactory;
use Wimski\Nominatim\Contracts\ClientInterface;
use Wimski\Nominatim\Contracts\GeocoderServiceInterface;
use Wimski\Nominatim\Contracts\Transformers\GeocodingResponseTransformerInterface;
use Wimski\Nominatim\GeocoderServices\GenericGeocoderService;
use Wimski\Nominatim\GeocoderServices\LocationIqGeocoderService;
use Wimski\Nominatim\GeocoderServices\NominatimGeocoderService;

class GeocoderServiceFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected GeocoderServiceFactory $factory;

    /**
     * @var ClientInterface|MockInterface
     */
    protected $client;

    /**
     * @var GeocodingResponseTransformerInterface|MockInterface
     */
    protected $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client      = Mockery::mock(ClientInterface::class);
        $this->transformer = Mockery::mock(GeocodingResponseTransformerInterface::class);

        $this->factory = new GeocoderServiceFactory(
            $this->client,
            $this->transformer,
        );
    }

    /**
     * @test
     */
    public function it_makes_a_nominatim_service(): void
    {
        $service = $this->makeServiceWithData([
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

        static::assertInstanceOf(NominatimGeocoderService::class, $service);
    }

    /**
     * @test
     */
    public function it_makes_a_location_iq_service(): void
    {
        $service = $this->makeServiceWithData([
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

        static::assertInstanceOf(LocationIqGeocoderService::class, $service);
    }

    /**
     * @test
     */
    public function it_makes_a_generic_service(): void
    {
        $service = $this->makeServiceWithData([
            'service'  => 'generic',
            'language' => 'nl',
            'services' => [
                'generic' => [
                    'url'                        => 'https://api.org',
                    'forward_geocoding_endpoint' => 'front',
                    'reverse_geocoding_endpoint' => 'back',
                ],
            ],
        ]);

        static::assertInstanceOf(GenericGeocoderService::class, $service);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_config_is_missing(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage('Nominatim config not found');

        $this->makeServiceWithData(null);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_service_is_not_supported(): void
    {
        static::expectException(RuntimeException::class);
        static::expectExceptionMessage("The config value 'nominatim.service' is not supported");

        $this->makeServiceWithData([
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

        $this->makeServiceWithData([
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

        $this->makeServiceWithData([
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
                'generic' => [
                    'url'                        => 'x',
                    'forward_geocoding_endpoint' => 'x',
                    'reverse_geocoding_endpoint' => 'x',
                ],
            ],
        ];

        Arr::forget($data, "services.{$service}.{$key}");

        $this->makeServiceWithData($data);
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
            ['generic', 'url'],
            ['generic', 'forward_geocoding_endpoint'],
            ['generic', 'reverse_geocoding_endpoint'],
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     * @return GeocoderServiceInterface
     */
    protected function makeServiceWithData(?array $data): GeocoderServiceInterface
    {
        /** @var Config|MockInterface $config */
        $config = Mockery::mock(Config::class)
            ->shouldReceive('get')
            ->once()
            ->with('nominatim')
            ->andReturn($data)
            ->getMock();

        return $this->factory->make($config);
    }
}
