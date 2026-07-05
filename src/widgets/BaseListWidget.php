<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\widgets;

/**
 * Base ranked-list Clicky Analytics widget.
 *
 * Extends the date-ranged widget with a configurable row limit, for the "top N"
 * widgets (pages, sources, countries, browsers).
 *
 * @author JohnHenry <info@johnhenry.ie>
 * @since 1.0.0
 */
abstract class BaseListWidget extends BaseStatsWidget
{
    // Public Properties
    // =========================================================================

    /**
     * @var int The maximum number of rows to show.
     */
    public int $limit = 10;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * Re-includes `limit`, which Craft's default implementation drops because it is
     * declared on this abstract base class; without this the per-widget row limit is
     * never persisted and reverts to its default on reload.
     *
     * @return string[] The settings attribute names.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    public function settingsAttributes(): array
    {
        return array_merge(parent::settingsAttributes(), ['limit']);
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
        $rules = parent::rules();
        $rules[] = [['limit'], 'integer', 'min' => 1, 'max' => 100];
        return $rules;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return bool Whether to show the limit field.
     * @author JohnHenry <info@johnhenry.ie>
     * @since 1.0.0
     */
    protected function showLimit(): bool
    {
        return true;
    }
}
