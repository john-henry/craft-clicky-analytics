<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Top Countries widget.
 *
 * Shows the top visitor countries for the widget's date range.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class TopCountries extends BaseListWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether to allow expanding a country to its cities and regions.
     */
    public bool $drilldown = true;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Top Countries');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Top Countries');
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['drilldown'], 'boolean'];
        return $rules;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function showDrilldown(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/_list';
    }

    /**
     * @inheritdoc
     */
    protected function bodyData(): array
    {
        return [
            'rows' => ClickyAnalytics::getInstance()->getApi()->getTopCountries($this->resolveRange(), $this->limit, $this->drilldown),
            'accent' => Palette::accent('countries'),
            'drilldown' => $this->drilldown,
            'date' => $this->resolveRange(),
        ];
    }
}
