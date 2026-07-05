<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use johnhenry\clickyanalytics\ClickyAnalytics;

/**
 * Live Visitors widget.
 *
 * A snapshot of the most recent visitors (location, flag, browser/OS, referrer
 * and dwell time) on the Craft dashboard, with configurable row count and a
 * new / returning / both filter.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class LiveVisitors extends BaseWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @var int How many visitors to show.
     */
    public int $limit = 10;

    /**
     * @var string Which visitors to show: all, new or returning.
     */
    public string $filter = 'all';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics: Live Visitors');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return Craft::t('clicky-analytics', 'Live Visitors');
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['limit'], 'integer', 'min' => 1, 'max' => 100];
        $rules[] = [['filter'], 'in', 'range' => ['all', 'new', 'returning']];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('clicky-analytics/_components/widgets/_liveSettings', [
            'widget' => $this,
        ]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function bodyTemplate(): string
    {
        return 'clicky-analytics/_components/_feed';
    }

    /**
     * @inheritdoc
     */
    protected function bodyData(): array
    {
        // Pull a deeper pool so filtering still yields enough rows.
        $pool = ClickyAnalytics::getInstance()->getApi()->getRecentVisitors('last-7-days', max(50, $this->limit));

        if ($this->filter === 'new') {
            $pool = array_filter($pool, static fn(array $v): bool => !$v['returning']);
        } elseif ($this->filter === 'returning') {
            $pool = array_filter($pool, static fn(array $v): bool => $v['returning']);
        }

        return [
            'visitors' => array_slice(array_values($pool), 0, $this->limit),
        ];
    }
}
