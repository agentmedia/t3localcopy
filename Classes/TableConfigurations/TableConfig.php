<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations;

use AgentMedia\T3LocalCopy\TableConfigurations\Condition\RecordConditionInterface;
class TableConfig {
    protected string $tableName;
    protected string $primaryColumn = 'uid';

    protected array $uidsForeignTableRelations = [];
    protected array $uidsMultipleForeignTableRelations = [];
    protected array $flexFieldForeignTableRelations = [];
    protected array $flexFieldMultipleForeignTableRelations = [];
    protected array $foreignChildRelations = [];
    protected array $foreignParentRelations = [];
    protected string $l10nParentColumn = 'l10n_parent';
    protected string $languageColumn = 'sys_language_uid';

    protected string $explicitSelectQuery = '';
    protected string $pidColumn = '';
    protected string $flexFormColumn = 'pi_flexform';
    protected array $omitColumns = [];

    protected bool $includeParents = false;

    /**
     * Construct a TableConfig, taking all necessary base arguments for configuration.
     * @param mixed $tableName The name of the table for which this configuration applies.
     * @param mixed $primaryColumn The primary column of the table, typically 'uid'.
     * @param string $explicitSelectQuery If set, all records matching this WHERE condition will be selected explicitly. Use '1=1' to select all records.
     * @param mixed $l10nParentColumn The column representing the localization parent, typically 'l10n_parent'.
     * @param mixed $languageColumn The column representing the language, typically 'sys_language_uid'.
     * @param mixed $pidColumn The column representing the parent ID, typically 'pid'.
     * @param bool $includeParents Whether to include parent records in the configuration.
     * @param string $flexFormColumn The column representing the flex form, typically 'pi_flexform'.
     * @param array $omitColumns The columns to omit from the configuration.
     */
    public function __construct($tableName, $primaryColumn = 'uid', string $explicitSelectQuery = '', $l10nParentColumn = 'l10n_parent', $languageColumn = 'sys_language_uid', $pidColumn = '', bool $includeParents = false, string $flexFormColumn = 'pi_flexform', array $omitColumns = []) {
        $this->tableName = $tableName;
        $this->primaryColumn = $primaryColumn;
        $this->explicitSelectQuery = $explicitSelectQuery;
        $this->l10nParentColumn = $l10nParentColumn;
        $this->languageColumn = $languageColumn;
        $this->pidColumn = $pidColumn;
        $this->includeParents = $includeParents;
        $this->flexFormColumn = $flexFormColumn;
        $this->omitColumns = $omitColumns;
    }

    /**
     * Gets the columns that are omitted from extraction.
     * 
     * @return array
     */
    public function getOmitColumns(): array {
        return $this->omitColumns;
    }

    /**
     * Gets the explicit select query for the table configuration. It is a where condition used to explicitly select records.
     * 
     * @return string
     */
    public function getExplicitSelectQuery(): string {
        return $this->explicitSelectQuery;
    }

    /**
     * Gets the pid column for the table configuration. The pid column is expected to relate to the table of this configuration. Typically, it is only used for the pages table.
     * 
     * @return string
     */
    public function getPidColumn(): string {
        return $this->pidColumn;
    }

    /**
     * Gets whether to include parent records in the configuration.
     * 
     * @return bool
     */
    public function getIncludeParents(): bool {
        return $this->includeParents;
    }

    /**
     * Gets the flex form column for the table configuration. Typically, this is only used for the tt_content table.
     * 
     * @return string
     */
    public function getFlexFormColumn(): string {
        return $this->flexFormColumn;
    }

    /**
     * Adds a UIDs foreign table relation for the table configuration.
     * 
     * @param string $foreignUidsColumn The column in the current table that holds the foreign UIDs.
     * @param string $foreignUidsTable The foreign table that holds the UIDs referenced by the current table.
     * @param RecordConditionInterface|null $recordCondition
     * @return void
     */
    public function addUidsForeignTableRelation($foreignUidsColumn, $foreignUidsTable, ?RecordConditionInterface $recordCondition = null) {
        $this->uidsForeignTableRelations[] = ['column' => $foreignUidsColumn, 'table' => $foreignUidsTable, 'recordCondition' => $recordCondition];
    }

    /**
     * Adds a UIDs multiple foreign tables relation for the table configuration.
     * 
     * @param string $foreignUidsColumn The column in the current table that holds the foreign UIDs.
     * @param array $allowedForeignUidsTables The list of allowed foreign tables that can be referenced by the UIDs in the current table.
     * @param RecordConditionInterface|null $recordCondition
     * @return void
     */
    public function addUidsMultipleForeignTablesRelation($foreignUidsColumn,  array $allowedForeignUidsTables, ?RecordConditionInterface $recordCondition = null) {
        $this->uidsMultipleForeignTableRelations[] = ['column' => $foreignUidsColumn, 'allowedTables' => $allowedForeignUidsTables, 'recordCondition' => $recordCondition];   
    }
    /**
     * Adds a foreign table relation for a specific flex field within a flex sheet. Only makes sense for tt_content.
     * 
     * @param string $flexSheet
     * @param string $flexField
     * @param string $foreignTable
     * @param RecordConditionInterface|null $recordCondition
     * @return void
     */
    public function addFlexFieldForeignTableRelation(string $flexSheet, string $flexField, string $foreignTable, ?RecordConditionInterface $recordCondition = null) {
        $this->flexFieldForeignTableRelations[] = ['flexSheet' => $flexSheet, 'flexField' => $flexField, 'table' => $foreignTable, 'recordCondition' => $recordCondition];
    }

    /**
     * Adds a multiple foreign tables relation for a specific flex field within a flex sheet. Only makes sense for tt_content.
     * 
     * @param string $flexSheet The name of the flex sheet within the tt_content table.
     * @param string $flexField The specific flex field within the flex sheet.
     * @param array $allowedForeignTables The list of allowed foreign tables that can be referenced by the flex field.
     * @param RecordConditionInterface|null $recordCondition The condition that must be met for the relation to be evaluated.
     * @return void
     */
    public function addFlexFieldMultipleForeignTablesRelation(string $flexSheet, string $flexField, array $allowedForeignTables, ?RecordConditionInterface $recordCondition = null) {
        $this->flexFieldMultipleForeignTableRelations[] = ['flexSheet' => $flexSheet, 'flexField' => $flexField, 'allowedTables' => $allowedForeignTables, 'recordCondition' => $recordCondition];
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
     * @param string $foreignParentColumn The column within this table that references the parent table. Typically, this is 'pid' for any record that is assigned to a typo3 page, for example tt_content.
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
     * Gets the flex field foreign table relations
     * @return array The flex field foreign table relations, an array with each element containing
     * 'flexSheet', 'flexField', 'table', and 'recordCondition' keys.
     */
    public function getFlexFieldForeignTableRelations(): array {
        return $this->flexFieldForeignTableRelations;
    }

    /**
     * Gets the flex field multiple foreign table relations
     * @return array The flex field multiple foreign table relations, an array with each element containing
     * 'flexSheet', 'flexField', 'allowedTables' and 'recordCondition' keys.
     */
    public function getFlexFieldMultipleForeignTableRelations(): array {
        return $this->flexFieldMultipleForeignTableRelations;
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