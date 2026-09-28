<?php

/**
 * @copyright Copyright (c) John Henry Donovan
 */

namespace johnhenry\clickyanalytics\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\services\ProjectConfig;
use yii\base\Exception;
use yii\db\Exception as DbException;

/**
 * Renames the `clicky-analytics:addWidgets` permission to
 * `clicky-analytics:view-analytics`, keeping every existing user and user group
 * grant. It now covers seeing Clicky data anywhere, not only adding widgets.
 *
 * Craft stores permission names lowercased, so the old handle sits in the
 * database as `addwidgets`. Group grants also live in project
 * config, which is rewritten once, on the environment that first runs this
 * migration; other environments pick it up from the YAML.
 *
 * @author John Henry Donovan <info@johnhenry.ie>
 * @since 1.1.0
 */
class m260925_000000_view_analytics_permission extends Migration
{
    // Const Properties
    // =========================================================================

    /**
     * @var string The old permission, as Craft stores it.
     */
    private const OLD_NAME = 'clicky-analytics:addwidgets';

    /**
     * @var string The new permission.
     */
    private const NEW_NAME = 'clicky-analytics:view-analytics';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return bool Whether the migration applied successfully.
     * @throws Exception if project config can't be updated.
     * @throws DbException if a permission row can't be updated.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function safeUp(): bool
    {
        $this->_renamePermissionRows();
        $this->_renameGroupPermissionsInProjectConfig();

        return true;
    }

    /**
     * @inheritdoc
     *
     * @return bool Always false; the rename isn't reverted.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    public function safeDown(): bool
    {
        echo "m260925_000000_view_analytics_permission cannot be reverted.\n";

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Renames each old permission row. Where a row with the new name already
     * exists, its grants are moved onto that row and the old one is deleted.
     *
     * @return void
     * @throws DbException if a permission row can't be updated.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _renamePermissionRows(): void
    {
        $rows = (new Query())
            ->select(['id', 'name'])
            ->from(Table::USERPERMISSIONS)
            ->where(['name' => self::OLD_NAME])
            ->all($this->db);

        foreach ($rows as $row) {
            $newName = $this->_newName($row['name']);
            $existingId = (new Query())
                ->select(['id'])
                ->from(Table::USERPERMISSIONS)
                ->where(['name' => $newName])
                ->scalar($this->db);

            if (!$existingId) {
                $this->update(Table::USERPERMISSIONS, ['name' => $newName], ['id' => $row['id']]);
                continue;
            }

            $this->_moveGrants(Table::USERPERMISSIONS_USERS, 'userId', (int)$row['id'], (int)$existingId);
            $this->_moveGrants(Table::USERPERMISSIONS_USERGROUPS, 'groupId', (int)$row['id'], (int)$existingId);
            $this->delete(Table::USERPERMISSIONS, ['id' => $row['id']]);
        }
    }

    /**
     * Copies the grants on one permission row to another, skipping any the
     * target already has.
     *
     * @param string $table The grants table.
     * @param string $ownerColumn The user or group ID column.
     * @param int $fromId The old permission ID.
     * @param int $toId The new permission ID.
     * @return void
     * @throws DbException if a grant can't be inserted.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _moveGrants(string $table, string $ownerColumn, int $fromId, int $toId): void
    {
        $existing = (new Query())
            ->select([$ownerColumn])
            ->from($table)
            ->where(['permissionId' => $toId])
            ->column($this->db);

        $owners = (new Query())
            ->select([$ownerColumn])
            ->from($table)
            ->where(['permissionId' => $fromId])
            ->andWhere(['not', [$ownerColumn => $existing]])
            ->column($this->db);

        foreach ($owners as $ownerId) {
            $this->insert($table, ['permissionId' => $toId, $ownerColumn => $ownerId]);
        }
    }

    /**
     * Rewrites the old permission names in every user group's project config,
     * unless another environment has already done so.
     *
     * @return void
     * @throws Exception if project config can't be updated.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _renameGroupPermissionsInProjectConfig(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.clicky-analytics.schemaVersion', true);

        if ($schemaVersion !== null && version_compare($schemaVersion, '1.1.0', '>=')) {
            return;
        }

        $groups = $projectConfig->get(ProjectConfig::PATH_USER_GROUPS) ?? [];
        $projectConfig->muteEvents = true;

        try {
            foreach ($groups as $uid => $group) {
                $permissions = $group['permissions'] ?? [];
                $renamed = array_map(fn(string $name): string => $this->_newName($name), $permissions);

                if ($renamed === $permissions) {
                    continue;
                }

                $projectConfig->set(
                    ProjectConfig::PATH_USER_GROUPS . ".$uid.permissions",
                    array_values(array_unique($renamed)),
                    'Rename the Clicky Analytics widget permission',
                );
            }
        } finally {
            $projectConfig->muteEvents = false;
        }
    }

    /**
     * Returns the new name for a permission, or the name unchanged if it
     * isn't the old widget permission.
     *
     * @param string $name The stored permission name.
     * @return string The renamed permission.
     * @author John Henry Donovan <info@johnhenry.ie>
     * @since 1.1.0
     */
    private function _newName(string $name): string
    {
        return strtolower($name) === self::OLD_NAME ? self::NEW_NAME : $name;
    }
}
