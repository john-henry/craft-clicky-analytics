<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\DateRanges;

/**
 * Base date-ranged Clicky Analytics widget.
 *
 * Adds a per-widget date-range setting (falling back to the plugin default) and
 * the shared settings UI, for widgets whose data is scoped to a period.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class BaseStatsWidget extends BaseWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @var string|null The widget's date range; null uses the plugin default.
     */
    public ?string $dateRange = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * Shows the widget's resolved date range (e.g. "Last 7 days") in Craft's
     * native widget header, next to the title, rather than duplicating it as
     * a line inside the widget body.
     *
     * @return string|null The resolved date range label.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSubtitle(): ?string
    {
        return $this->resolved()['label'];
    }

    /**
     * @inheritdoc
     *
     * Re-includes `dateRange`, which Craft's default implementation drops because
     * it is declared on this abstract base class; without this the per-widget date
     * range is never persisted and reverts to the plugin default on reload.
     *
     * @return string[] The settings attribute names.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), ['dateRange']);
    }

    /**
     * @inheritdoc
     *
     * @return array The validation rules.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['dateRange'], 'string'];
        return $rules;
    }

    /**
     * @inheritdoc
     *
     * @return string|null The widget settings HTML.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('clicky-analytics/_components/widgets/_settings', [
            'widget' => $this,
            'rangeOptions' => DateRanges::widgetOptions(),
            'showLimit' => $this->showLimit(),
            'showConsolidate' => $this->showConsolidate(),
            'showDrilldown' => $this->showDrilldown(),
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Resolves the widget's date range (key + comparison period + Clicky dates),
     * falling back to the plugin default.
     *
     * @return array{date: string, previous: string, label: string, start: string, end: string} The resolved range.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function resolved(): array
    {
        $default = ClickyAnalytics::getInstance()->getSettings()->defaultDateRange;
        return DateRanges::resolve(DateRanges::normalize($this->dateRange, $default));
    }

    /**
     * Returns the resolved Clicky date for the widget's range.
     *
     * @return string A Clicky date expression.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function resolveRange(): string
    {
        return $this->resolved()['date'];
    }

    /**
     * Returns whether the settings UI should expose a row-limit field.
     *
     * @return bool Whether to show the limit field.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function showLimit(): bool
    {
        return false;
    }

    /**
     * Returns whether the settings UI should expose a "consolidate versions"
     * toggle (for the browser and operating-system widgets).
     *
     * @return bool Whether to show the consolidate toggle.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function showConsolidate(): bool
    {
        return false;
    }

    /**
     * Returns whether the settings UI should expose a "drilldown" toggle (for the
     * countries widget).
     *
     * @return bool Whether to show the drilldown toggle.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function showDrilldown(): bool
    {
        return false;
    }
}
