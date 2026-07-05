<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

use Craft;
use craft\base\Widget;
use johnhenry\clickyanalytics\assetbundles\ClickyAsset;
use johnhenry\clickyanalytics\ClickyAnalytics;
use Throwable;

/**
 * Base Clicky Analytics widget.
 *
 * Centralises the rendering flow shared by every Clicky dashboard widget: bail
 * to a "not configured" notice when credentials are missing, fetch the widget's
 * data, and fall back to an error notice if the API call fails. Subclasses just
 * declare their body template and supply the data.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class BaseWidget extends Widget
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string|null The widget icon path.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function icon(): ?string
    {
        return dirname(__DIR__) . '/icon-mask.svg';
    }

    /**
     * @inheritdoc
     *
     * Only users with the "Add Clicky dashboard widgets" permission may add
     * these widgets (admins always may).
     *
     * @return bool Whether the widget can be selected.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public static function isSelectable(): bool
    {
        return parent::isSelectable()
            && Craft::$app->getUser()->checkPermission('clicky-analytics:addWidgets');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string|null The widget body HTML.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getBodyHtml(): ?string
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ClickyAsset::class);

        if (!ClickyAnalytics::getInstance()->getApi()->isConfigured()) {
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
     * Returns the path to the widget's body template.
     *
     * @return string The template path.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    abstract protected function bodyTemplate(): string;

    /**
     * Returns the variables passed to the widget's body template.
     *
     * @return array The template variables.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    abstract protected function bodyData(): array;
}
