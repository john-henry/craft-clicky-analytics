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
 * @author John Henry Donovan <info@johnhenry.ie>
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
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function actionTest(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        $settings = ClickyAnalytics::getInstance()->getSettings();
        $siteId = self::_resolve((string)$this->request->getBodyParam('siteId', ''), $settings->siteId);
        $siteKey = self::_resolve((string)$this->request->getBodyParam('siteKey', ''), $settings->siteKey);

        $result = ClickyAnalytics::getInstance()->getApi()->ping($siteId, $siteKey);

        return $this->asJson($result);
    }

    // Private Methods
    // =========================================================================

    /**
     * Resolves a submitted credential for the connection test.
     *
     * An `$ENV_VAR` reference is only resolved when it's the saved value or
     * names a `CLICKY_` variable, so the test can't be used to send another
     * secret (the database password, the security key) to Clicky. An empty
     * value, sent for a field the config file has locked, tests the saved
     * setting. Anything else is tested as typed.
     *
     * @param string $value The submitted value.
     * @param string|null $saved The saved setting.
     * @return string The value to test.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private static function _resolve(string $value, ?string $saved): string
    {
        $value = trim($value);

        if ($value === '') {
            return trim((string)App::parseEnv($saved ?? ''));
        }

        if (!str_starts_with($value, '$')) {
            return $value;
        }

        if ($value !== $saved && !str_starts_with(strtoupper($value), '$CLICKY_')) {
            return '';
        }

        return trim((string)App::parseEnv($value));
    }
}
