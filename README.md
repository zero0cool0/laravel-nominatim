[![Latest Stable Version](http://poser.pugx.org/wimski/laravel-nominatim/v)](https://packagist.org/packages/wimski/laravel-nominatim)
[![Coverage Status](https://coveralls.io/repos/github/wimski/laravel-nominatim/badge.svg?branch=master)](https://coveralls.io/github/wimski/laravel-nominatim?branch=master)
[![PHPUnit](https://github.com/wimski/laravel-nominatim/actions/workflows/phpunit.yml/badge.svg)](https://github.com/wimski/laravel-nominatim/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/wimski/laravel-nominatim/actions/workflows/phpstan.yml/badge.svg)](https://github.com/wimski/laravel-nominatim/actions/workflows/phpstan.yml)

# Laravel integration for Nominatim Geocoding API Client

This package is a Laravel integration of the [Nominatim Geocoding API Client](https://packagist.org/packages/wimski/nominatim-geocoding-api-client).

## Changelog

[View the changelog.](./CHANGELOG.md)

## Usage

### Install package

```bash
composer require wimski/laravel-nominatim
```

### Example

```php
use Wimski\Nominatim\Contracts\GeocoderServiceInterface;
use Wimski\Nominatim\Objects\Coordinate;
use Wimski\Nominatim\RequestParameters\ForwardGeocodingQueryRequestParameters;

class MyClass
{
    public function __construct(
        protected GeocoderServiceInterface $geocoder,
    ) {
    }
    
    public function queryCoordinate(string $query): Coordinate
    {
        $requestParameters = ForwardGeocodingQueryRequestParameters::make($query)
            ->addCountryCode('nl')
            ->includeAddressDetails();
            
        $response = $this->geocoder->requestForwardGeocoding($request);
        
        return $response->getItems()[0]->getCoordinate();
    }
}
```

### PSR HTTP

The underlying `Client` class uses [Discovery](https://docs.php-http.org/en/latest/discovery.html) by default to get instances of the following contracts:

* `Psr\Http\Client\ClientInterface`
* `Psr\Http\Message\RequestFactoryInterface`
* `Psr\Http\Message\UriFactoryInterface`

This means that you need to include (a) PSR compatible package(s) [in your project](https://docs.php-http.org/en/latest/httplug/users.html).

If you already have setup a specific HTTP client configuration in your project,
which you would also like to use for Nominatim requests,
you can pass these in by extending the service provider.

#### 1. Disable package discovery

`composer.json`
```json
"extra": {
    "laravel": {
        "dont-discover": [
            "wimski/laravel-nominatim"
        ]
    }
}
```

#### 2. Extend service provider

```php
<?php

namespace App\Providers;

use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Wimski\LaravelNominatim\Providers\NominatimServiceProvider as ServiceProvider;

class NominatimServiceProvider extends ServiceProvider
{
    protected function getHttpClient() : ?HttpClientInterface
    {
        // return your configured HTTP client here
    }
    
    protected function getRequestFactory() : ?RequestFactoryInterface
    {
        // return your configured request factory here
    }
    
    protected function getUriFactory() : ?UriFactoryInterface
    {
        // return your configured uri factory here
    }
}
```

#### 3. Include extended service provider

`config/app.config`

```php
return [
    // ...
    
    'providers' => [
    
        /*
         * Application Service Providers...
         */
         App\Providers\NominatimServiceProvider::class,
    ],
    
    // ...
];
```

### Services

Services for the following providers are currently available:
* [Nominatim](https://nominatim.org/release-docs/latest/api/Overview/)
  * `NOMINATIM_SERVICE=nominatim`
  * `NOMINATIM_NOMINATIM_USER_AGENT=` (required)
  * `NOMINATIM_NOMINATIM_EMAIL=` (required)
* [LocationIQ](https://locationiq.com/docs)
  * `NOMINATIM_SERVICE=location_iq`
  * `NOMINATIM_LOCATION_IQ_KEY=` (access token, required)
* Generic
  * `NOMINATIM_SERVICE=generic`

## PHPUnit

```bash
composer run phpunit
```

## PHPStan

```bash
composer run phpstan
```

## Credits

- [wimski](https://github.com/wimski)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
