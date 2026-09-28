<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\helpers;

use Craft;
use DateTime;
use DateTimeZone;

/**
 * Date range helper.
 *
 * Defines the date ranges offered in the widget and field settings, and
 * resolves each to a Clicky `date` value (https://clicky.com/help/api#dates).
 * Relative presets are sent as Clicky's own keywords, so "today" is today in
 * the Clicky site's time zone rather than Craft's; the period they're compared
 * against is worked out from the range Clicky reports back. Calendar months
 * are sent as explicit date ranges.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class DateRanges
{
    // Const Properties
    // =========================================================================

    /**
     * @var string The fallback range when none is valid.
     */
    public const DEFAULT = 'last-7-days';

    /**
     * @var string Stands in for a comparison period that's worked out from the
     * range Clicky reports for the current period.
     */
    public const PREVIOUS_PERIOD = 'previous-period';

    // Public Methods
    // =========================================================================

    /**
     * Returns the relative date range presets, keyed by their Clicky keyword.
     *
     * @return array<string, string> The relative date range options.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function relativeOptions(): array
    {
        return [
            'today' => Craft::t('clicky-analytics', 'Today'),
            'yesterday' => Craft::t('clicky-analytics', 'Yesterday'),
            '2-days-ago' => Craft::t('clicky-analytics', '2 days ago'),
            'last-7-days' => Craft::t('clicky-analytics', 'Last 7 days'),
            'last-14-days' => Craft::t('clicky-analytics', 'Last 14 days'),
            'last-28-days' => Craft::t('clicky-analytics', 'Last 28 days'),
            'last-60-days' => Craft::t('clicky-analytics', 'Last 60 days'),
            'last-90-days' => Craft::t('clicky-analytics', 'Last 90 days'),
        ];
    }

    /**
     * Returns the current and two previous calendar months, keyed `YYYY-MM`.
     *
     * @return array<string, string> The month options.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function months(): array
    {
        $month = new DateTime('first day of this month', new DateTimeZone(Craft::$app->getTimeZone()));
        $options = [];

        for ($i = 0; $i < 3; $i++) {
            $options[$month->format('Y-m')] = $month->format('M Y');
            $month->modify('-1 month');
        }

        return $options;
    }

    /**
     * Returns the date range options offered in widget and field settings.
     *
     * @return array<string, string> The widget date range options.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function widgetOptions(): array
    {
        return self::relativeOptions() + self::months();
    }

    /**
     * Returns whether a value is a recognised range key.
     *
     * @param string|null $value The candidate value.
     * @return bool Whether it is recognised.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function isValid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return isset(self::relativeOptions()[$value]) || self::_isMonth($value);
    }

    /**
     * Returns the value if it's a recognised range key, otherwise the default.
     *
     * @param string|null $value The candidate value.
     * @param string $default The fallback value.
     * @return string A recognised range key.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function normalize(?string $value, string $default = self::DEFAULT): string
    {
        if (self::isValid($value)) {
            return $value;
        }

        return self::isValid($default) ? $default : self::DEFAULT;
    }

    /**
     * Returns whether a value is a date Clicky accepts from a caller: a range
     * key, or a `YYYY-MM-DD` date or `YYYY-MM-DD,YYYY-MM-DD` range. Clicky
     * treats anything else as today without saying so.
     *
     * @param string $value The candidate value.
     * @return bool Whether it is accepted.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public static function isClickyDate(string $value): bool
    {
        return self::isValid($value) || preg_match('/^\d{4}-\d{2}-\d{2}(,\d{4}-\d{2}-\d{2})?$/', $value) === 1;
    }

    /**
     * Resolves a range key to the Clicky date to request, what to compare it
     * with, and its label.
     *
     * @param string $key A range key (relative preset or `YYYY-MM`).
     * @return array{date: string, previous: string, label: string} The resolved range.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function resolve(string $key): array
    {
        if (self::_isMonth($key)) {
            $from = new DateTime($key . '-01');
            $to = (clone $from)->modify('last day of this month');
            $prevFrom = (clone $from)->modify('-1 month');
            $prevTo = (clone $prevFrom)->modify('last day of this month');

            return [
                'date' => $from->format('Y-m-d') . ',' . $to->format('Y-m-d'),
                'previous' => $prevFrom->format('Y-m-d') . ',' . $prevTo->format('Y-m-d'),
                'label' => self::months()[$key] ?? $from->format('M Y'),
            ];
        }

        $key = isset(self::relativeOptions()[$key]) ? $key : self::DEFAULT;

        return [
            'date' => $key,
            'previous' => self::PREVIOUS_PERIOD,
            'label' => self::relativeOptions()[$key],
        ];
    }

    /**
     * Returns the equal-length period immediately before a Clicky date range,
     * as a Clicky date. Takes the range as Clicky reports it (`YYYY-MM-DD` or
     * `YYYY-MM-DD,YYYY-MM-DD`), which is already in the Clicky site's time zone.
     *
     * @param string $range The reported range.
     * @return string|null The preceding period, or null if the range can't be read.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public static function precedingPeriod(string $range): ?string
    {
        $parts = explode(',', $range);
        $from = DateTime::createFromFormat('!Y-m-d', $parts[0]);
        $to = DateTime::createFromFormat('!Y-m-d', $parts[1] ?? $parts[0]);

        if ($from === false || $to === false || $from > $to) {
            return null;
        }

        $length = (int)$from->diff($to)->format('%a') + 1;
        $prevTo = (clone $from)->modify('-1 day');
        $prevFrom = (clone $prevTo)->modify('-' . ($length - 1) . ' days');

        return $length === 1
            ? $prevTo->format('Y-m-d')
            : $prevFrom->format('Y-m-d') . ',' . $prevTo->format('Y-m-d');
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether a value is a `YYYY-MM` month key.
     *
     * @param string $value The candidate value.
     * @return bool Whether it is a month key.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private static function _isMonth(string $value): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1;
    }
}
