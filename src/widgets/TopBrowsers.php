<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Top Browsers widget.
 *
 * Shows the top web browsers for the widget's date range.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class TopBrowsers extends BaseListWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function showConsolidate(): bool
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
            'rows' => $this->api()->getTopBrowsers($this->resolveRange(), $this->limit, $this->consolidate),
            'accent' => Palette::accent('browsers'),
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @var bool Whether to merge browser versions into one row each.
     */
    public bool $consolidate = true;

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
        return Craft::t('clicky-analytics', 'Clicky Analytics: Top Browsers');
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
        return Craft::t('clicky-analytics', 'Top Browsers');
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
        $rules[] = [['consolidate'], 'boolean'];
        return $rules;
    }
}
