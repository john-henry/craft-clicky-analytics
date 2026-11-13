<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\models\GqlSchema;
use CraftCms\Cms\Cp\Icons;
use johnhenry\clickyanalytics\assetbundles\ClickyAsset;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\DateRanges;
use johnhenry\clickyanalytics\services\Api;
use Throwable;

/**
 * Clicky Analytics page-stats field.
 *
 * An informational field for an entry type's field layout that shows Clicky
 * analytics for the element's own URL: headline tallies, traffic sources and
 * recent visitors. It stores no data of its own (the value is always derived
 * live from the element's URL).
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class PageStats extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The date range to report on. Clicky keeps the per-page data this reads for about 30 days.
     */
    public string $dateRange = 'last-28-days';

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function displayName(): string
    {
        return Craft::t('clicky-analytics', 'Clicky Analytics Page Stats');
    }

    /**
     * @inheritdoc
     *
     * Craft 6's field type menu takes the name of one of Craft's own icons and
     * shows nothing for a file path, so the plugin's icon is only used on Craft 5.
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function icon(): string
    {
        if (class_exists(Icons::class)) {
            return 'chart-line';
        }

        return dirname(__DIR__) . '/icon-mask.svg';
    }

    /**
     * @inheritdoc
     *
     * This field derives everything live from the element's URL and stores no value.
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function dbType(): array|string|null
    {
        return null;
    }

    /**
     * The field stores nothing, so it can never be filled in; marked required,
     * it would stop every save.
     *
     * @return bool Always false.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public static function isRequirable(): bool
    {
        return false;
    }

    /**
     * Keeps the field out of GraphQL schemas. It stores no value, so Craft's
     * fallback string type would always be null, and the stats it shows are
     * behind a user permission that schema scopes don't know about.
     *
     * @param GqlSchema $schema The schema being built.
     * @return bool Always false.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSettingsHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ClickyAsset::class);

        return $view->renderTemplate('clicky-analytics/_components/field/settings', [
            'field' => $this,
            'rangeOptions' => DateRanges::widgetOptions(),
        ]);
    }

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return $this->_render($element);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
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
     * Users without the view permission get nothing.
     *
     * @param ElementInterface|null $element The element being edited.
     * @return string The rendered HTML.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    private function _render(?ElementInterface $element): string
    {
        if (!Craft::$app->getUser()->checkPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)) {
            return '';
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(ClickyAsset::class);

        $api = ClickyAnalytics::getInstance()->getApi()->forSite($element?->getSite());

        if (!$api->isConfigured()) {
            return $view->renderTemplate('clicky-analytics/_components/field/_notice', [
                'message' => Craft::t('clicky-analytics', 'Add your Clicky Site ID and Sitekey in the plugin settings.'),
            ]);
        }

        // An unpublished draft only has a temporary URL, which has no stats.
        $url = $element !== null && !$element->getIsUnpublishedDraft() ? $element->getUrl() : null;
        if ($url === null || $url === '') {
            return $view->renderTemplate('clicky-analytics/_components/field/_notice', [
                'message' => Craft::t('clicky-analytics', 'Stats will appear here once this entry has a URL.'),
            ]);
        }

        $href = Api::pathFromUrl($url);
        $range = DateRanges::resolve(DateRanges::normalize($this->dateRange, 'last-28-days'));
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
