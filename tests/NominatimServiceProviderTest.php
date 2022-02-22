<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Tests;

use Orchestra\Testbench\TestCase;
use Wimski\Nominatim\Contracts\GeocoderServiceInterface;
use Wimski\Nominatim\RequestParameters\ForwardGeocodingQueryRequestParameters;

class NominatimServiceProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_makes_a_nominatim_forward_geocoding_query_request(): void
    {
        $this->app['config']->set('nominatim.service', 'nominatim');

        /** @var GeocoderServiceInterface $service */
        $service = $this->app->make(GeocoderServiceInterface::class);

        $request = ForwardGeocodingQueryRequestParameters::make('query');

        $response = $service->requestForwardGeocoding($request);

        static::assertCount(2, $response->getItems());

        static::assertSame(12345, $response->getItems()[0]->getPlaceId());
        static::assertSame('Beautiful Building', $response->getItems()[0]->getDisplayName());

        static::assertSame(67890, $response->getItems()[1]->getPlaceId());
        static::assertSame('Statue of Something', $response->getItems()[1]->getDisplayName());
    }

    /**
     * @test
     */
    public function it_makes_a_location_iq_forward_geocoding_query_request(): void
    {
        $this->app['config']->set('nominatim.service', 'location_iq');

        /** @var GeocoderServiceInterface $service */
        $service = $this->app->make(GeocoderServiceInterface::class);

        $request = ForwardGeocodingQueryRequestParameters::make('query');

        $response = $service->requestForwardGeocoding($request);

        static::assertCount(2, $response->getItems());

        static::assertSame(12345, $response->getItems()[0]->getPlaceId());
        static::assertSame('Beautiful Building', $response->getItems()[0]->getDisplayName());

        static::assertSame(67890, $response->getItems()[1]->getPlaceId());
        static::assertSame('Statue of Something', $response->getItems()[1]->getDisplayName());
    }

    protected function getPackageProviders($app): array
    {
        return [
            ExtendedServiceProvider::class,
        ];
    }
}
