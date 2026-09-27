<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use johnhenry\clickyanalytics\helpers\DateRanges;
use johnhenry\clickyanalytics\helpers\Palette;

/**
 * Clicky Analytics settings model.
 *
 * Holds the Clicky API credentials and dashboard defaults. The Site ID and
 * Sitekey both support environment-variable syntax (e.g. `$CLICKY_SITE_KEY`),
 * parsed lazily through the getters so secrets stay out of project config.
 * Sites that track into their own Clicky site can override both per Craft
 * site handle through `$sites`.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
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
     * @var bool Whether the ranked-list bars use a slim style instead of full height.
     */
    public bool $slimBarStyle = false;

    /**
     * @var array<string, array{siteId?: string, siteKey?: string}> Per-site Clicky credentials, keyed by
     * Craft site handle, for sites that track into their own Clicky site. Each value may be an `$ENV_VAR`
     * reference. A site that isn't listed uses `$siteId` and `$siteKey`.
     */
    public array $sites = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return array The behavior configurations.
     * @author John Henry Donovan <info@johnhenry.ie>
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
     * @return array<int, mixed> The validation rules.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['siteId', 'siteKey'], 'required'],
            [['siteId', 'siteKey', 'defaultDateRange', 'colorScheme'], 'string'],
            [['siteId'], 'validateSiteId'],
            [['defaultDateRange'], 'in', 'range' => array_keys(DateRanges::relativeOptions())],
            [['colorScheme'], 'in', 'range' => array_keys(Palette::schemes())],
            [['cacheDuration'], 'integer', 'min' => 0],
            [['injectTrackingCode', 'trackNoScript', 'compactDensity', 'slimBarStyle'], 'boolean'],
        ]);
    }

    /**
     * Checks that the Site ID resolves to a number, as Clicky's Site IDs are.
     *
     * @param string $attribute The attribute being validated.
     * @return void
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function validateSiteId(string $attribute): void
    {
        $siteId = $this->getSiteId();

        if ($siteId !== null && !ctype_digit($siteId)) {
            $this->addError($attribute, Craft::t('clicky-analytics', 'The Site ID should be a number.'));
        }
    }

    /**
     * Returns the resolved Site ID, parsing any environment-variable reference.
     *
     * @param string|null $siteHandle A Craft site handle, to use that site's own Clicky site when it has one.
     * @return string|null The parsed Site ID, or null if unset.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSiteId(?string $siteHandle = null): ?string
    {
        return self::_resolve($this->_siteCredential($siteHandle, 'siteId') ?? $this->siteId);
    }

    /**
     * Returns the resolved Sitekey, parsing any environment-variable reference.
     *
     * @param string|null $siteHandle A Craft site handle, to use that site's own Clicky site when it has one.
     * @return string|null The parsed Sitekey, or null if unset.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function getSiteKey(?string $siteHandle = null): ?string
    {
        return self::_resolve($this->_siteCredential($siteHandle, 'siteKey') ?? $this->siteKey);
    }

    /**
     * Returns the handles of the Craft sites that have their own Clicky Site
     * ID and Sitekey in `$sites`.
     *
     * @return string[] The site handles.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function getSiteHandlesWithOwnCredentials(): array
    {
        return array_values(array_filter(
            array_keys($this->sites),
            fn($handle): bool => $this->_siteCredential((string)$handle, 'siteId') !== null,
        ));
    }

    /**
     * Returns the CSS modifier classes for the current density and bar style,
     * for the `.clicky-widget` / `.clicky-field` / `.clicky-panel` root elements.
     *
     * @return string The space-separated modifier classes.
     * @author John Henry Donovan <info@johnhenry.ie>
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

    // Private Methods
    // =========================================================================

    /**
     * Returns a site's own credential from `$sites`, or null when the site has
     * no override. The Site ID and Sitekey are taken as a pair: a site with
     * only one of them set falls back to the default pair for both.
     *
     * @param string|null $siteHandle The Craft site handle.
     * @param string $name `siteId` or `siteKey`.
     * @return string|null The raw credential, or null.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _siteCredential(?string $siteHandle, string $name): ?string
    {
        if ($siteHandle === null) {
            return null;
        }

        $site = $this->sites[$siteHandle] ?? null;
        if (!is_array($site) || self::_resolve($site['siteId'] ?? null) === null || self::_resolve($site['siteKey'] ?? null) === null) {
            return null;
        }

        return (string)$site[$name];
    }

    /**
     * Parses an environment-variable reference and trims the result.
     *
     * @param string|null $value The raw value.
     * @return string|null The resolved value, or null if empty.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private static function _resolve(?string $value): ?string
    {
        return trim((string)App::parseEnv($value)) ?: null;
    }
}
