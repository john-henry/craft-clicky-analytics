<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;

/**
 * Clicky Analytics settings model.
 *
 * Holds the Clicky API credentials and dashboard defaults. The Site ID and
 * Sitekey both support environment-variable syntax (e.g. `$CLICKY_SITEKEY`),
 * parsed lazily through the getters so secrets stay out of project config.
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string|null The Clicky Site ID (numeric, or an `$ENV_VAR` reference).
     */
    public ?string $siteId = null;

    /**
     * @var string|null The Clicky Sitekey (or an `$ENV_VAR` reference).
     */
    public ?string $siteKey = null;

    /**
     * @var string The default date range used by the dashboard and widgets.
     */
    public string $defaultDateRange = 'last-7-days';

    /**
     * @var int How long, in seconds, to cache Clicky API responses.
     */
    public int $cacheDuration = 300;

    /**
     * @var string The colour scheme used across the widgets and field.
     */
    public string $colorScheme = 'clicky';

    /**
     * @var bool Whether to inject the Clicky tracking snippet on front-end requests.
     */
    public bool $injectTrackingCode = false;

    /**
     * @var bool Whether the injected snippet includes the `<noscript>` pixel fallback.
     */
    public bool $trackNoScript = true;

    /**
     * @var bool Whether the CP UI uses a compact density instead of comfortable.
     */
    public bool $compactDensity = false;

    /**
     * @var bool Whether the toolbar uses a slim style instead of the full bar.
     */
    public bool $slimBarStyle = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return array The behavior configurations.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['siteId', 'siteKey'],
            ],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return array The validation rules.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function rules(): array
    {
        return [
            [['siteId', 'siteKey'], 'required'],
            [['siteId', 'siteKey', 'defaultDateRange', 'colorScheme'], 'string'],
            [['cacheDuration'], 'integer', 'min' => 0],
            [['injectTrackingCode', 'trackNoScript', 'compactDensity', 'slimBarStyle'], 'boolean'],
        ];
    }

    /**
     * Returns the resolved Site ID, parsing any environment-variable reference.
     *
     * @return string|null The parsed Site ID, or null if unset.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSiteId(): ?string
    {
        return App::parseEnv($this->siteId) ?: null;
    }

    /**
     * Returns the resolved Sitekey, parsing any environment-variable reference.
     *
     * @return string|null The parsed Sitekey, or null if unset.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSiteKey(): ?string
    {
        return App::parseEnv($this->siteKey) ?: null;
    }

    /**
     * Returns the CSS modifier classes for the current density and bar style,
     * for the `.clicky-widget` / `.clicky-field` / `.clicky-panel` root elements.
     *
     * @return string The space-separated modifier classes.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getUiModifierClass(): string
    {
        $classes = [
            $this->compactDensity ? 'clicky--density-compact' : 'clicky--density-comfortable',
            $this->slimBarStyle ? 'clicky--bar-slim' : 'clicky--bar-full',
        ];

        return implode(' ', $classes);
    }
}
