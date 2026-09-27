<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\controllers;

use craft\web\Controller;
use johnhenry\clickyanalytics\ClickyAnalytics;
use johnhenry\clickyanalytics\helpers\DateRanges;
use Throwable;
use yii\web\Response;

/**
 * Country controller.
 *
 * Serves the cities/regions breakdown for a country as a rendered HTML partial,
 * backing the Top Countries widget's drilldown.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.0.0
 */
class CountryController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var array<int|string>|bool|int Whether the controller's actions can be accessed anonymously.
     */
    protected array|bool|int $allowAnonymous = false;

    // Public Methods
    // =========================================================================

    /**
     * Returns the cities and regions breakdown for a country.
     *
     * @return Response The JSON response carrying the rendered HTML.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function actionIndex(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(ClickyAnalytics::PERMISSION_VIEW_ANALYTICS);

        // Country names are short; anything longer can't match a report row.
        $country = substr((string)$this->request->getRequiredParam('country'), 0, 100);

        // The date goes to Clicky and into the cache key, so only a date
        // Clicky understands is let through.
        $date = (string)$this->request->getParam('date');
        if (!DateRanges::isClickyDate($date)) {
            $date = DateRanges::resolve(DateRanges::DEFAULT)['date'];
        }

        $site = $this->request->getParam('site');
        $plugin = ClickyAnalytics::getInstance();
        if (!in_array($site, $plugin->getSettings()->getSiteHandlesWithOwnCredentials(), true)) {
            $site = null;
        }

        try {
            $breakdown = $plugin->getApi()->forSite($site)->getCountryBreakdown($country, $date);
        } catch (Throwable) {
            $breakdown = ['cities' => [], 'regions' => []];
        }

        $html = $this->getView()->renderTemplate('clicky-analytics/_components/_country', [
            'cities' => $breakdown['cities'],
            'regions' => $breakdown['regions'],
        ]);

        return $this->asJson(['html' => $html]);
    }
}
