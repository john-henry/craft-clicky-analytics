<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use craft\base\Widget;
use johnhenry\clickyanalytics\assetbundles\ClickyAsset;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\services\Api;
use Throwable;

/**
 * Base Clicky Analytics widget.
 *
 * Centralises the rendering flow shared by every Clicky dashboard widget: show
 * nothing to users without the view permission, bail to a "not configured"
 * notice when credentials are missing, fetch the widget's data, and fall back
 * to an error notice if the API call fails. Subclasses just declare their body
 * template and supply the data.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class BaseWidget extends Widget
{
    // Public Properties
    // =========================================================================

    /**
     * Returns the API service for the Clicky site the widget reports on.
     *
     * @return Api The API service.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    protected function api(): Api
    {
        return ClickyAnalytics::getInstance()->getApi()->forSite($this->siteHandle);
    }

    /**
     * Returns the name of the Craft site the widget reports on, or null for
     * the main Clicky site.
     *
     * @return string|null The site name.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    protected function siteName(): ?string
    {
        if ($this->siteHandle === null || $this->siteHandle === '') {
            return null;
        }

        return Craft::$app->getSites()->getSiteByHandle($this->siteHandle, true)?->getName();
    }

    /**
     * Renders the Clicky site setting, or an empty string when no Craft site
     * has its own Clicky credentials to choose from.
     *
     * @return string The setting's HTML.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    protected function siteSettingHtml(): string
    {
        $options = [];
        foreach (ClickyAnalytics::getInstance()->getSettings()->getSiteHandlesWithOwnCredentials() as $handle) {
            $site = Craft::$app->getSites()->getSiteByHandle($handle, true);
            if ($site !== null) {
                $options[$handle] = $site->getName();
            }
        }

        if ($options === []) {
            return '';
        }

        return Craft::$app->getView()->renderTemplate('clicky-analytics/_components/widgets/_siteSetting', [
            'widget' => $this,
            'siteOptions' => $options,
        ]);
    }

    /**
     * Returns the path to the widget's body template.
     *
     * @return string The template path.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    abstract protected function bodyTemplate(): string;

    /**
     * Returns the variables passed to the widget's body template.
     *
     * @return array The template variables.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    abstract protected function bodyData(): array;

    // Protected Methods
    // =========================================================================

    /**
     * @var string|null The handle of the Craft site whose Clicky site the widget reports on, or null for the
     * main Clicky site. Only sites with their own Clicky credentials can be chosen.
     */
    public ?string $siteHandle = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string|null The widget icon path.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function icon(): ?string
    {
        return dirname(__DIR__) . '/icon-mask.svg';
    }

    /**
     * @inheritdoc
     *
     * Only users with the view permission may add these widgets (admins
     * always may).
     *
     * @return bool Whether the widget can be selected.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function isSelectable(): bool
    {
        return parent::isSelectable()
            && Craft::$app->getUser()->checkPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS);
    }

    /**
     * @inheritdoc
     *
     * Re-includes `siteHandle`, which Craft's default implementation drops
     * because it is declared on this abstract base class.
     *
     * @return string[] The settings attribute names.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), ['siteHandle']);
    }

    /**
     * Returns the name of the Craft site the widget reports on, when it isn't
     * the main Clicky site.
     *
     * @return string|null The subtitle.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function getSubtitle(): ?string
    {
        return $this->siteName();
    }

    /**
     * @inheritdoc
     *
     * Widgets without settings of their own still offer the Clicky site.
     *
     * @return string|null The widget settings HTML.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function getSettingsHtml(): ?string
    {
        return $this->siteSettingHtml() ?: null;
    }

    /**
     * Refuses to save the widget for a user without the view permission.
     * Craft's own create-widget action doesn't check {@see isSelectable()}.
     *
     * @param bool $isNew Whether the widget is new.
     * @return bool Whether the save can go ahead.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function beforeSave(bool $isNew): bool
    {
        return Craft::$app->getUser()->checkPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)
            && parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     *
     * @return string|null The widget body HTML.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getBodyHtml(): ?string
    {
        // Craft leaves out a widget whose body is null, so a user who can't see
        // Clicky data doesn't get it, however the widget got on their dashboard.
        if (!Craft::$app->getUser()->checkPermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS)) {
            return null;
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(ClickyAsset::class);

        if (!$this->api()->isConfigured()) {
            return $view->renderTemplate('clicky-analytics/_components/widgets/_notConfigured');
        }

        try {
            $vars = $this->bodyData();
        } catch (Throwable $e) {
            Craft::error('Clicky widget failed to load: ' . $e->getMessage(), 'clicky-analytics');
            return $view->renderTemplate('clicky-analytics/_components/widgets/_error', [
                'error' => Craft::t('clicky-analytics', 'Couldn’t load Clicky data. Check the logs for details.'),
            ]);
        }

        $vars['clickyUiClass'] = ClickyAnalytics::getInstance()->getSettings()->getUiModifierClass();

        return $view->renderTemplate($this->bodyTemplate(), $vars);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return array The validation rules.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['siteHandle'], 'default', 'value' => null];
        $rules[] = [['siteHandle'], 'in', 'range' => ClickyAnalytics::getInstance()->getSettings()->getSiteHandlesWithOwnCredentials()];
        return $rules;
    }
}
