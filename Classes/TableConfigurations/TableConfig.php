<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations;

use AgentMedia\T3LocalCopy\TableConfigurations\Condition\RecordConditionInterface;
class TableConfig {
    protected string $tableName;
    protected string $primaryColumn = 'uid';

    protected array $uidsForeignTableRelations = [];
    protected array $uidsMultipleForeignTableRelations = [];
    protected array $foreignChildRelations = [];
    protected array $foreignParentRelations = [];
    protected string $l10nParentColumn = 'l10n_parent';
    protected string $languageColumn = 'sys_language_uid';

    protected string $explicitSelectQuery = '';
    protected string $pidColumn = '';

    protected bool $includeParents = false;

    public function __construct($tableName, $primaryColumn = 'uid', string $explicitSelectQuery = '', $l10nParentColumn = 'l10n_parent', $languageColumn = 'sys_language_uid', $pidColumn = '', bool $includeParents = false) {
        $this->tableName = $tableName;
        $this->primaryColumn = $primaryColumn;
        $this->explicitSelectQuery = $explicitSelectQuery;
        $this->l10nParentColumn = $l10nParentColumn;
        $this->languageColumn = $languageColumn;
        $this->pidColumn = $pidColumn;
        $this->includeParents = $includeParents;
    }

    public function getExplicitSelectQuery(): string {
        return $this->explicitSelectQuery;
    }


    public function getPidColumn(): string {
        return $this->pidColumn;
    }

    public function getIncludeParents(): bool {
        return $this->includeParents;
    }

    public function addUidsForeignTableRelation($foreignUidsColumn, $foreignUidsTable, ?RecordConditionInterface $recordCondition = null) {
        $this->uidsForeignTableRelations[] = ['column' => $foreignUidsColumn, 'table' => $foreignUidsTable, 'recordCondition' => $recordCondition];
    }

    public function addUidsMultipleForeignTablesRelation($foreignUidsColumn,  array $allowedForeignUidsTables, ?RecordConditionInterface $recordCondition = null) {
        $this->uidsMultipleForeignTableRelations[] = ['column' => $foreignUidsColumn, 'allowedTables' => $allowedForeignUidsTables, 'recordCondition' => $recordCondition];   
    }

    /**
     * Adds a child relation for the table configuration. Example for pages config related to its child contents: $foreignParentColumn would be 'pid', $foreignTable would be 'tt_content'.
     *
     * @param string $foreignParentColumn The column within the child table that references the parent table.
     * @param string $foreignTable The name of the child table.
     * @param array $additionalConditions Additional conditions for the relation.
     * @param RecordConditionInterface $recordCondition The condition that must be met for the relation to be evaluated.
     * @return void
     */
    public function addForeignChildRelation(string $foreignParentColumn, string $foreignTable, array $additionalConditions = [], ?RecordConditionInterface $recordCondition = null) {
        $this->foreignChildRelations[] = ['column' => $foreignParentColumn, 'table' => $foreignTable, 'conditions' => $additionalConditions, 'recordCondition' => $recordCondition];
    }

    /**
     * Adds a foreign parent relation for the table configuration. Example for tt_content config related to its parent pages: $foreignParentColumn would be 'pid', $foreignTable would be 'pages'.
     * @param string $foreignParentColumn The column within this table that references the parent table.
     * @param string $foreignTable The name of the parent table.
     * @param RecordConditionInterface $recordCondition The condition that must be met for the relation to be evaluated.
     * @return void
     */
    public function addForeignParentRelation(string $foreignParentColumn, string $foreignTable, ?RecordConditionInterface $recordCondition = null) {
        $this->foreignParentRelations[] = ['column' => $foreignParentColumn, 'table' => $foreignTable, 'recordCondition' => $recordCondition];
    }

    /**
     * 
     * Gets the table name
     * @return string The name of the table
     */
    public function getTableName(): string {
        return $this->tableName;
    }

    /**
     * Gets the primary column name
     * @return string The name of the primary column
     */
    public function getPrimaryColumn(): string {
        return $this->primaryColumn;
    }

    /**
     * Gets the UIDs foreign table relations
     * @return array The UIDs foreign table relations, an array with each element containing
     * 'column', 'table', and 'recordCondition' keys.
     */
    public function getUidsForeignTableRelations(): array {
        return $this->uidsForeignTableRelations;
    }
    
    /**
     * Gets the foreign parent relations
     * @return array The foreign parent relations, an array with each element containing 
     * 'column', 'table' and 'recordCondition' keys .
     */
    public function getForeignParentRelations(): array {
        return $this->foreignParentRelations;
    }

    /**
     * Gets the UIDs multiple foreign table relations
     * @return array The UIDs multiple foreign table relations, an array with each element containing 
     * 'column' and 'allowedTables' keys.
     */
    public function getUidsMultipleForeignTableRelations(): array {
        return $this->uidsMultipleForeignTableRelations;
    }

    /**
     * Gets the foreign child relations
     * @return array The foreign child relations, an array with each element containing 
     * 'column', 'table', 'conditions', and 'recordCondition' keys.
     */
    public function getForeignChildRelations(): array {
        return $this->foreignChildRelations;
    }

    /**
     * Gets the localization parent column name
     * @return string The name of the localization parent column
     */
    public function getL10nParentColumn(): string {
        return $this->l10nParentColumn;
    }

    /**
     * Gets the language column name
     * @return string The name of the language column
     */
    public function getLanguageColumn(): string {
        return $this->languageColumn;
    }
}