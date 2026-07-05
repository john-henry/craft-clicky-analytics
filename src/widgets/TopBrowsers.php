<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Top Browsers widget.
 *
 * Shows the top web browsers for the widget's date range.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class TopBrowsers extends BaseListWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether to merge browser versions into one row each.
     */
    public bool $consolidate = true;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Top Browsers');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Top Browsers');
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['consolidate'], 'boolean'];
        return $rules;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function showConsolidate(): bool
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
            'rows' => ClickyAnalytics::getInstance()->getApi()->getTopBrowsers($this->resolveRange(), $this->limit, $this->consolidate),
            'accent' => Palette::accent('browsers'),
        ];
    }
}
