<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\DateRanges;
use johnhenry\clickyanalytics\helpers\Palette;
use johnhenry\clickyanalytics\services\Api;

/**
 * Configurable report widget.
 *
 * A single widget that renders any Clicky metric in the chosen visualisation
 * (counter, line, bar or pie), inspired by Verbb's Metrix. The display type drives
 * which metrics make sense; the widget renders defensively for combinations that
 * don't (e.g. a total shown as a pie falls back to a counter).
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class ReportWidget extends BaseListWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The visualisation type (counter/line/bar/pie).
     */
    public string $display = 'counter';

    /**
     * @var string The metric/report key.
     */
    public string $metric = 'visitors';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Report');
    }

    /**
     * Returns the available visualisation types.
     *
     * @return array<string, string> The display options.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function displayOptions(): array
    {
        return [
            'counter' => Craft::t('clicky-analytics', 'Counter'),
            'line' => Craft::t('clicky-analytics', 'Line'),
            'bar' => Craft::t('clicky-analytics', 'Bar'),
            'pie' => Craft::t('clicky-analytics', 'Pie'),
        ];
    }

    /**
     * Returns the single-number metrics.
     *
     * @return array<string, string> The total metric options.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function totalOptions(): array
    {
        return [
            'visitors' => Craft::t('clicky-analytics', 'Visitors'),
            'actions' => Craft::t('clicky-analytics', 'Actions'),
            'bounce-rate' => Craft::t('clicky-analytics', 'Bounce rate'),
            'time-average' => Craft::t('clicky-analytics', 'Average time'),
            'visitors-online' => Craft::t('clicky-analytics', 'Online now'),
        ];
    }

    /**
     * Returns the ranked-report metrics.
     *
     * @return array<string, string> The report metric options.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function reportOptions(): array
    {
        return [
            'pages' => Craft::t('clicky-analytics', 'Top pages'),
            'traffic-sources' => Craft::t('clicky-analytics', 'Top sources'),
            'links-domains' => Craft::t('clicky-analytics', 'Top referrals'),
            'searches-engines' => Craft::t('clicky-analytics', 'Search engines'),
            'countries' => Craft::t('clicky-analytics', 'Countries'),
            'web-browsers' => Craft::t('clicky-analytics', 'Browsers'),
            'operating-systems' => Craft::t('clicky-analytics', 'Operating systems'),
            'hardware' => Craft::t('clicky-analytics', 'Devices'),
            'pages-entrance' => Craft::t('clicky-analytics', 'Entry pages'),
            'pages-exit' => Craft::t('clicky-analytics', 'Exit pages'),
            'downloads' => Craft::t('clicky-analytics', 'Downloads'),
            'goals' => Craft::t('clicky-analytics', 'Goals'),
            'campaigns' => Craft::t('clicky-analytics', 'Campaigns'),
            'cities' => Craft::t('clicky-analytics', 'Cities'),
            'regions' => Craft::t('clicky-analytics', 'Regions'),
            'screen-resolutions' => Craft::t('clicky-analytics', 'Screen resolutions'),
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['display', 'metric'], 'string'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        $label = self::totalOptions()[$this->metric] ?? self::reportOptions()[$this->metric] ?? Craft::t('clicky-analytics', 'Report');
        return Craft::t('clicky-analytics', 'Clicky Analytics') . ': ' . $label;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('clicky-analytics/_components/widgets/_reportSettings', [
            'widget' => $this,
            'displayOptions' => self::displayOptions(),
            'totalOptions' => self::totalOptions(),
            'reportOptions' => self::reportOptions(),
            'rangeOptions' => DateRanges::widgetOptions(),
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/_report';
    }

    /**
     * @inheritdoc
     */
    protected function bodyData(): array
    {
        $api = ClickyAnalytics::getInstance()->getApi();
        $date = $this->resolveRange();
        $isTotal = isset(self::totalOptions()[$this->metric]);

        // Line is a time series; only tallies make sense, default to visitors.
        if ($this->display === 'line') {
            $tally = in_array($this->metric, ['visitors', 'actions'], true) ? $this->metric : 'visitors';
            return [
                'display' => 'line',
                'accent' => Palette::primary(),
                'series' => $api->getDailySeries($tally, $date),
            ];
        }

        // Counter, or a total metric forced into bar/pie/table → show a counter.
        if ($this->display === 'counter' || $isTotal) {
            return [
                'display' => 'counter',
                'accent' => Palette::primary(),
                'value' => $this->_totalValue($api, $date),
                'label' => self::totalOptions()[$this->metric]
                    ?? self::reportOptions()[$this->metric]
                    ?? $this->metric,
            ];
        }

        // Bar or pie of a ranked report.
        return [
            'display' => $this->display,
            'accent' => Palette::primary(),
            'rows' => $api->getReport($this->metric, $date, $this->limit),
        ];
    }

    // Private Methods
    // =========================================================================

    /**
     * Resolves the metric to a display-ready single value for the counter.
     *
     * @param Api $api The API service.
     * @param string $date A Clicky date expression.
     * @return string The formatted value.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _totalValue(Api $api, string $date): string
    {
        if ($this->metric === 'visitors-online') {
            return Craft::$app->getFormatter()->asInteger($api->getVisitorsOnline());
        }

        if (isset(self::totalOptions()[$this->metric])) {
            $value = $api->getTally($this->metric, $date);

            return match ($this->metric) {
                'bounce-rate' => $value . '%',
                'time-average' => $value < 60 ? $value . 's' : intdiv($value, 60) . 'm ' . ($value % 60) . 's',
                default => Craft::$app->getFormatter()->asInteger($value),
            };
        }

        // A ranked report shown as a counter → the sum of its values.
        $sum = 0;
        foreach ($api->getReport($this->metric, $date, 100) as $row) {
            $sum += (int)($row['value'] ?? 0);
        }

        return Craft::$app->getFormatter()->asInteger($sum);
    }
}
