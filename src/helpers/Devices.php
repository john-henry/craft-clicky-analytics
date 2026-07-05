<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

/**
 * Device helper.
 *
 * Maps Clicky hardware names (e.g. "Apple iPhone", "Android device") to a
 * device-type icon name (phone / tablet / desktop / device) used by the UI.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class Devices
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the icon name for a Clicky hardware title.
     *
     * @param string $title The hardware title from Clicky.
     * @return string One of: phone, tablet, desktop, device.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function icon(string $title): string
    {
        $haystack = strtolower($title);

        return match (true) {
            str_contains($haystack, 'ipad'), str_contains($haystack, 'tablet') => 'tablet',
            str_contains($haystack, 'iphone'), str_contains($haystack, 'phone'), str_contains($haystack, 'android') => 'phone',
            str_contains($haystack, 'mac'), str_contains($haystack, 'windows'), str_contains($haystack, 'desktop'), str_contains($haystack, 'linux'), str_contains($haystack, 'pc') => 'desktop',
            default => 'device',
        };
    }
}
