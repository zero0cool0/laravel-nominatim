<?php

declare(strict_types=1);

namespace Wimski\LaravelNominatim\Enums;

use MyCLabs\Enum\Enum;

/**
 * @extends Enum<string>
 * @method static static GENERIC()
 * @method static static LOCATION_IQ()
 * @method static static NOMINATIM()
 */
class ServiceEnum extends Enum
{
    public const GENERIC     = 'generic';
    public const LOCATION_IQ = 'location_iq';
    public const NOMINATIM   = 'nominatim';
}
