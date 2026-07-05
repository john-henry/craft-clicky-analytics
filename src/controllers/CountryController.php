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
 * @author JohnHenry <info@johnhenry.ie>
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
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function actionIndex(): Response
    {
        $this->requireCpRequest();
        $this->requireAcceptsJson();
        $this->requirePermission('clicky-analytics:addWidgets');

        // Country names are short; cap the input so a malformed request can't
        // bloat the two cached report fetches it feeds into.
        $country = substr((string)$this->request->getRequiredParam('country'), 0, 100);
        $date = (string)$this->request->getParam('date') ?: DateRanges::resolve(DateRanges::DEFAULT)['date'];

        try {
            $breakdown = ClickyAnalytics::getInstance()->getApi()->getCountryBreakdown($country, $date);
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
