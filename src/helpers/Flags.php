<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

/**
 * Country flag helper.
 *
 * Clicky returns country names (e.g. "Ireland", "The United States") with no ISO
 * code, so this maps the common names to ISO 3166-1 alpha-2 codes. The lower-case
 * code resolves to a bundled flag SVG (flag-icons); unknown countries return null
 * and the UI shows a globe instead.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class Flags
{
    // Constants
    // =========================================================================

    /**
     * @var array<string, string> Country name (lower-case, no leading "the") to ISO 3166-1 alpha-2.
     */
    private const CODES = [
        'ireland' => 'IE',
        'united states' => 'US',
        'united states of america' => 'US',
        'usa' => 'US',
        'united kingdom' => 'GB',
        'uk' => 'GB',
        'great britain' => 'GB',
        'england' => 'GB',
        'scotland' => 'GB',
        'wales' => 'GB',
        'northern ireland' => 'GB',
        'canada' => 'CA',
        'australia' => 'AU',
        'new zealand' => 'NZ',
        'germany' => 'DE',
        'france' => 'FR',
        'spain' => 'ES',
        'portugal' => 'PT',
        'italy' => 'IT',
        'netherlands' => 'NL',
        'the netherlands' => 'NL',
        'belgium' => 'BE',
        'luxembourg' => 'LU',
        'switzerland' => 'CH',
        'austria' => 'AT',
        'sweden' => 'SE',
        'norway' => 'NO',
        'denmark' => 'DK',
        'finland' => 'FI',
        'iceland' => 'IS',
        'poland' => 'PL',
        'czech republic' => 'CZ',
        'czechia' => 'CZ',
        'slovakia' => 'SK',
        'hungary' => 'HU',
        'romania' => 'RO',
        'bulgaria' => 'BG',
        'greece' => 'GR',
        'croatia' => 'HR',
        'slovenia' => 'SI',
        'serbia' => 'RS',
        'ukraine' => 'UA',
        'russia' => 'RU',
        'russian federation' => 'RU',
        'turkey' => 'TR',
        'estonia' => 'EE',
        'latvia' => 'LV',
        'lithuania' => 'LT',
        'india' => 'IN',
        'china' => 'CN',
        'japan' => 'JP',
        'south korea' => 'KR',
        'korea' => 'KR',
        'taiwan' => 'TW',
        'hong kong' => 'HK',
        'singapore' => 'SG',
        'malaysia' => 'MY',
        'indonesia' => 'ID',
        'thailand' => 'TH',
        'vietnam' => 'VN',
        'philippines' => 'PH',
        'pakistan' => 'PK',
        'bangladesh' => 'BD',
        'israel' => 'IL',
        'united arab emirates' => 'AE',
        'saudi arabia' => 'SA',
        'qatar' => 'QA',
        'south africa' => 'ZA',
        'nigeria' => 'NG',
        'kenya' => 'KE',
        'egypt' => 'EG',
        'morocco' => 'MA',
        'brazil' => 'BR',
        'argentina' => 'AR',
        'chile' => 'CL',
        'colombia' => 'CO',
        'mexico' => 'MX',
        'peru' => 'PE',
        'uruguay' => 'UY',
    ];

    // Public Methods
    // =========================================================================

    /**
     * Returns the lower-case ISO 3166-1 alpha-2 code for a country name, or null
     * if it isn't in the map. The code matches a bundled flag SVG filename.
     *
     * @param string $country The country name from Clicky.
     * @return string|null The lower-case ISO code, or null if unknown.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function code(string $country): ?string
    {
        $key = strtolower(trim($country));
        $key = preg_replace('/^the\s+/', '', $key) ?? $key;

        $code = self::CODES[$key] ?? null;

        return $code !== null ? strtolower($code) : null;
    }
}
