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
 * Defines the date ranges offered in the dashboard date picker and widget
 * settings, and resolves each to a concrete Clicky `date` value
 * (https://clicky.com/help/api#dates) together with the equal-length preceding
 * period used for trend comparison. Supports relative presets, recent calendar
 * months, and an arbitrary custom range.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class DateRanges
{
    // Constants
    // =========================================================================

    /**
     * @var string The fallback range when none is valid.
     */
    public const DEFAULT = 'last-7-days';

    // Public Methods
    // =========================================================================

    /**
     * Returns the relative date presets as `value => label` pairs. These are the
     * static options offered for widget settings and the plugin default.
     *
     * @return array<string, string> The relative date range options.
     * @author JohnHenry <info@johnhenry.ie>
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
     * Returns the most recent calendar months as `YYYY-MM => "Mon YYYY"` pairs
     * (current month plus the two before it).
     *
     * @return array<string, string> The month options.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function months(): array
    {
        $month = (new DateTime('first day of this month', self::_tz()));
        $options = [];

        for ($i = 0; $i < 3; $i++) {
            $options[$month->format('Y-m')] = $month->format('M Y');
            $month->modify('-1 month');
        }

        return $options;
    }

    /**
     * Returns every dashboard date picker option (relative presets, recent
     * months and the custom-range entry) as `value => label` pairs.
     *
     * @return array<string, string> The full set of date range options.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function options(): array
    {
        return self::relativeOptions()
            + self::months()
            + ['custom' => Craft::t('clicky-analytics', 'Custom date range…')];
    }

    /**
     * Returns the date options offered in widget settings: relative presets and
     * recent months, but no custom range (widgets are persisted, not interactive).
     *
     * @return array<string, string> The widget date range options.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function widgetOptions(): array
    {
        return self::relativeOptions() + self::months();
    }

    /**
     * Returns whether the given value is a recognised range key.
     *
     * @param string|null $value The candidate value.
     * @return bool Whether it is recognised.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function isValid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return isset(self::relativeOptions()[$value])
            || $value === 'custom'
            || preg_match('/^\d{4}-\d{2}$/', $value) === 1;
    }

    /**
     * Normalises a candidate range key, falling back to the given default.
     *
     * @param string|null $value The candidate value.
     * @param string $default The fallback value.
     * @return string A recognised range key.
     * @author JohnHenry <info@johnhenry.ie>
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
     * Resolves a range key (plus optional custom start/end) to concrete Clicky
     * date values for the period, its preceding comparison period, and a label.
     *
     * @param string $key A range key (relative, `YYYY-MM`, or `custom`).
     * @param string|null $start A `Y-m-d` start date (custom ranges only).
     * @param string|null $end A `Y-m-d` end date (custom ranges only).
     * @return array{date: string, previous: string, label: string, start: string, end: string} The resolved range.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function resolve(string $key, ?string $start = null, ?string $end = null): array
    {
        [$from, $to] = self::_bounds($key, $start, $end);

        $startStr = $from->format('Y-m-d');
        $endStr = $to->format('Y-m-d');

        // Equal-length window immediately before this one, for trends.
        $length = (int)$from->diff($to)->format('%a') + 1;
        $prevTo = (clone $from)->modify('-1 day');
        $prevFrom = (clone $prevTo)->modify('-' . ($length - 1) . ' days');

        if ($key === 'custom') {
            $label = $from->format('j M Y') . ' – ' . $to->format('j M Y');
        } else {
            $label = self::options()[$key] ?? $key;
        }

        return [
            'date' => self::_format($startStr, $endStr),
            'previous' => self::_format($prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d')),
            'label' => $label,
            'start' => $startStr,
            'end' => $endStr,
        ];
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the [start, end] DateTime bounds for a range key, capping the end
     * at today and falling back to the default range when input is invalid.
     *
     * @param string $key A range key.
     * @param string|null $start A custom `Y-m-d` start date.
     * @param string|null $end A custom `Y-m-d` end date.
     * @return array{0: DateTime, 1: DateTime} The start and end bounds.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private static function _bounds(string $key, ?string $start, ?string $end): array
    {
        $tz = self::_tz();
        $today = new DateTime('today', $tz);

        // Custom range: validate both dates, swap if reversed, cap at today.
        if ($key === 'custom') {
            $from = $start !== null ? DateTime::createFromFormat('Y-m-d', $start, $tz) : false;
            $to = $end !== null ? DateTime::createFromFormat('Y-m-d', $end, $tz) : false;

            if ($from === false || $to === false) {
                return [(clone $today)->modify('-6 days'), $today];
            }

            $from->setTime(0, 0);
            $to->setTime(0, 0);

            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            if ($to > $today) {
                $to = $today;
            }

            return [$from, $to];
        }

        // A specific calendar month (YYYY-MM).
        if (preg_match('/^\d{4}-\d{2}$/', $key) === 1) {
            $from = DateTime::createFromFormat('Y-m-d', $key . '-01', $tz);
            if ($from === false) {
                return [(clone $today)->modify('-6 days'), $today];
            }
            $from->setTime(0, 0);
            $to = (clone $from)->modify('last day of this month');

            return [$from, $to > $today ? $today : $to];
        }

        // Relative presets.
        return match ($key) {
            'today' => [$today, $today],
            'yesterday' => [(clone $today)->modify('-1 day'), (clone $today)->modify('-1 day')],
            '2-days-ago' => [(clone $today)->modify('-2 days'), (clone $today)->modify('-2 days')],
            'last-14-days' => [(clone $today)->modify('-13 days'), $today],
            'last-28-days' => [(clone $today)->modify('-27 days'), $today],
            'last-60-days' => [(clone $today)->modify('-59 days'), $today],
            'last-90-days' => [(clone $today)->modify('-89 days'), $today],
            default => [(clone $today)->modify('-6 days'), $today],
        };
    }

    /**
     * Formats a start/end pair as a Clicky `date` value (single date or range).
     *
     * @param string $start A `Y-m-d` start date.
     * @param string $end A `Y-m-d` end date.
     * @return string The Clicky date expression.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private static function _format(string $start, string $end): string
    {
        return $start === $end ? $start : $start . ',' . $end;
    }

    /**
     * Returns the site time zone.
     *
     * @return DateTimeZone The site time zone.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private static function _tz(): DateTimeZone
    {
        return new DateTimeZone(Craft::$app->getTimeZone());
    }
}
