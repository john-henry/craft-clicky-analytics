<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Top Countries widget.
 *
 * Shows the top visitor countries for the widget's date range.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class TopCountries extends BaseListWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function showDrilldown(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/widgets/_list';
    }

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function bodyData(): array
    {
        return [
            'rows' => $this->api()->getTopCountries($this->resolveRange(), $this->limit, $this->drilldown),
            'accent' => Palette::accent('countries'),
            'drilldown' => $this->drilldown,
            'date' => $this->resolveRange(),
            'site' => $this->siteHandle,
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @var bool Whether to allow expanding a country to its cities and regions.
     */
    public bool $drilldown = true;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Top Countries');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Top Countries');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['drilldown'], 'boolean'];
        return $rules;
    }
}
