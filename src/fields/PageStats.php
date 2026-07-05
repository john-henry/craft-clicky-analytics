<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use johnhenry\clickyanalytics\assetbundles\ClickyAsset;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\DateRanges;
use Throwable;

/**
 * Clicky Analytics page-stats field.
 *
 * An informational field for an entry type's field layout that shows Clicky
 * analytics for the element's own URL: headline tallies, traffic sources and
 * recent visitors. It stores no data of its own (the value is always derived
 * live from the element's URL).
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class PageStats extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The date range to report on.
     */
    public string $dateRange = 'last-90-days';

    /**
     * @var int How many recent visitors to show.
     */
    public int $visitorLimit = 5;

    /**
     * @var bool Whether to show the summary cards (visitors, actions, etc.).
     */
    public bool $showSummary = true;

    /**
     * @var bool Whether to show the traffic sources.
     */
    public bool $showSources = true;

    /**
     * @var bool Whether to show the recent visitors list.
     */
    public bool $showVisitors = true;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics Page Stats');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return dirname(__DIR__) . '/icon-mask.svg';
    }

    /**
     * @inheritdoc
     *
     * This field derives everything live from the element's URL and stores no value.
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('clicky-analytics/_components/field/settings', [
            'field' => $this,
            'rangeOptions' => DateRanges::widgetOptions(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_render($element);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return $this->_render($element);
    }

    // Private Methods
    // =========================================================================

    /**
     * Renders the stats panel for the element's URL, with friendly notices when
     * the plugin isn't configured, the element has no URL yet, or the API fails.
     *
     * @param ElementInterface|null $element The element being edited.
     * @return string The rendered HTML.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _render(?ElementInterface $element): string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ClickyAsset::class);

        $api = ClickyAnalytics::getInstance()->getApi();

        if (!$api->isConfigured()) {
            return $view->renderTemplate('clicky-analytics/_components/field/_notice', [
                'message' => Craft::t('clicky-analytics', 'Add your Clicky Site ID and Sitekey in the plugin settings.'),
            ]);
        }

        $url = $element?->getUrl();
        if ($url === null || $url === '') {
            return $view->renderTemplate('clicky-analytics/_components/field/_notice', [
                'message' => Craft::t('clicky-analytics', 'Stats will appear here once this entry has a URL.'),
            ]);
        }

        $href = parse_url($url, PHP_URL_PATH) ?: '/';
        $range = DateRanges::resolve(DateRanges::normalize($this->dateRange, 'last-90-days'));
        $visitorLimit = $this->showVisitors ? $this->visitorLimit : 0;

        try {
            $data = $api->getPageStats($href, $range['date'], $visitorLimit);
        } catch (Throwable $e) {
            Craft::error('Clicky page-stats field failed to load: ' . $e->getMessage(), 'clicky-analytics');
            return $view->renderTemplate('clicky-analytics/_components/field/_notice', [
                'message' => Craft::t('clicky-analytics', 'Couldn’t load Clicky data. Check the logs for details.'),
            ]);
        }

        return $view->renderTemplate('clicky-analytics/_components/field/page-stats', [
            'data' => $data,
            'href' => $href,
            'url' => $url,
            'rangeLabel' => $range['label'],
            'showSummary' => $this->showSummary,
            'showSources' => $this->showSources,
            'showVisitors' => $this->showVisitors,
            'clickyUiClass' => ClickyAnalytics::getInstance()->getSettings()->getUiModifierClass(),
        ]);
    }
}
