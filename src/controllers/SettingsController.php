<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\controllers;

use craft\helpers\App;
use craft\web\Controller;
use johnhenry\clickyanalytics\ClickyAnalytics;
use yii\web\Response;

/**
 * Clicky Analytics settings controller.
 *
 * Backs the "Test connection" button on the settings screen, validating the
 * entered Site ID / Sitekey (resolving any environment variables) against the
 * live Clicky API.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class SettingsController extends Controller
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
     * Tests the submitted Clicky credentials.
     *
     * @return Response The JSON test outcome.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function actionTest(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        $siteId = (string)App::parseEnv((string)$this->request->getBodyParam('siteId', ''));
        $siteKey = (string)App::parseEnv((string)$this->request->getBodyParam('siteKey', ''));

        $result = ClickyAnalytics::getInstance()->getApi()->ping($siteId, $siteKey);

        return $this->asJson($result);
    }
}
