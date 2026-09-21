<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Reader;

use AgentMedia\T3LocalCopy\TableConfigurations\Reader\ConfigTypeRegistry;
use AgentMedia\T3LocalCopy\TableConfigurations\Condition\DirectRecordCondition;
use AgentMedia\T3LocalCopy\TableConfigurations\Condition\RecordConditionInterface;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfigRegistry;

/**
 * Reads a JSON config file and returns a TableConfigRegistry with all table configs.
 * JSON should look like this:
 * {
 *   "tableConfigRegistry" : [
 *     {
 *       "table": "tt_content",
 *       "primaryColumn": "uid",
 *       "explicitSelectQuery": "",
 *       "l10nParentColumn": "l18n_parent",
 *       "languageColumn": "sys_language_uid",
 *       "pidColumn": "",
 *       "includeParents": false,
 *       "uidsForeignTableRelations": [
 *         {
 *           "column": "foreign_uid_column",
 *           "table": "foreign_table",
 *           "recordCondition": {
 *             "__type__": "DirectRecordCondition",
 *             "arguments": [
 *                 {"CType": "text"}
 *             ]
 *           }
 *         }
 *       ]
 *     }
 *   ]
 * }
 *    
 * 
 * 
 * 
 */
class ConfigReader {
    private ConfigTypeRegistry $configTypeRegistry;

    public function __construct(?ConfigTypeRegistry $configTypeRegistry = null) {
        if (!$configTypeRegistry) {
            $configTypeRegistry = new ConfigTypeRegistry();
            $configTypeRegistry->register(DirectRecordCondition::class, 'DirectRecordCondition');
        }
        $this->configTypeRegistry = $configTypeRegistry;
    }

    public function read(array $config): TableConfigRegistry {
        $tableConfigRegistry = new TableConfigRegistry();
        if (!isset($config['tableConfigRegistry'])) {
            throw new \InvalidArgumentException('Invalid config: missing tableConfigRegistry as root element.');
        }
        foreach ($config['tableConfigRegistry'] ?? [] as $tableConfigData) {
            $tableConfig = $this->createTableConfig($tableConfigData);
            $this->addUidsForeignTableRelations($tableConfig, $tableConfigData['uidsForeignTableRelations'] ?? []);
            $this->addUidsMultipleForeignTablesRelations($tableConfig, $tableConfigData['uidsMultipleForeignTablesRelations'] ?? []);
            $this->addFlexFieldForeignTableRelations($tableConfig, $tableConfigData['flexFieldForeignTableRelations'] ?? []);
            $this->addFlexFieldMultipleForeignTablesRelations($tableConfig, $tableConfigData['flexFieldMultipleForeignTableRelations'] ?? []);
            $this->addForeignChildRelation($tableConfig, $tableConfigData['foreignChildRelations'] ?? []);
            $this->addForeignParentRelations($tableConfig, $tableConfigData['foreignParentRelations'] ?? []);
            $tableConfigRegistry->registerTableConfig($tableConfig);
        }
        return $tableConfigRegistry;
    }

    public function readJsonFile(string $filePath): TableConfigRegistry {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: $filePath");
        }
        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: $filePath");
        }
        $json = file_get_contents($filePath);
        return $this->readJsonString($json);
    }

    public function readJsonString(string $json): TableConfigRegistry {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON, could not be parsed as an array.');
        }
        return $this->read($data);
    }

    private function addUidsForeignTableRelations(TableConfig $tableConfig, array $uidsForeignTableRelations): void {
        foreach ($uidsForeignTableRelations as $relation) {
            $table = $relation['table'] ?? '';
            $column = $relation['column'] ?? '';
            if (empty($table) || empty($column)) {
                continue;
            }
            $recordCondition = $this->processRecordCondition($relation['recordCondition'] ?? null);
            $tableConfig->addUidsForeignTableRelation($column, $table, $recordCondition);
        }
    }

    private function addForeignChildRelation(TableConfig $tableConfig, array $foreignChildRelations): void {
        foreach ($foreignChildRelations as $relation) {
            $table = $relation['table'] ?? '';
            $column = $relation['column'] ?? '';
            $additionalConditions = $relation['additionalConditions'] ?? [];
            if (empty($table) || empty($column)) {
                continue;
            }
            $recordCondition = $this->processRecordCondition($relation['recordCondition'] ?? null);
            $tableConfig->addForeignChildRelation($column, $table, $additionalConditions, $recordCondition);
        }
    }

    private function addUidsMultipleForeignTablesRelations(TableConfig $tableConfig, array $uidsMultipleForeignTablesRelations): void {
        foreach ($uidsMultipleForeignTablesRelations as $relation) {
            $allowedTables = $relation['tables'] ?? [];
            $column = $relation['column'] ?? '';
            
            if (empty($allowedTables) || !is_array($allowedTables) || empty($column)) {
                continue;
            }
            $recordCondition = $this->processRecordCondition($relation['recordCondition'] ?? null);
            $tableConfig->addUidsMultipleForeignTablesRelation($column, $allowedTables, $recordCondition);
        }
    }

    private function addFlexFieldForeignTableRelations(TableConfig $tableConfig, array $flexFieldForeignTableRelations): void {
        foreach ($flexFieldForeignTableRelations as $relation) {
            $flexSheet = $relation['flexSheet'] ?? '';
            $flexField = $relation['flexField'] ?? '';
            $foreignTable = $relation['table'] ?? '';
            if (empty($flexSheet) || empty($flexField) || empty($foreignTable)) {
                continue;
            }
            $recordCondition = $this->processRecordCondition($relation['recordCondition'] ?? null);
            $tableConfig->addFlexFieldForeignTableRelation($flexSheet, $flexField, $foreignTable, $recordCondition);
        }
    }

    private function addFlexFieldMultipleForeignTablesRelations(TableConfig $tableConfig, array $flexFieldMultipleForeignTableRelations): void {
        foreach ($flexFieldMultipleForeignTableRelations as $relation) {
            $flexSheet = $relation['flexSheet'] ?? '';
            $flexField = $relation['flexField'] ?? '';
            $allowedTables = $relation['allowedTables'] ?? [];
            if (empty($flexSheet) || empty($flexField) || empty($allowedTables) || !is_array($allowedTables)) {
                continue;
            }
            $recordCondition = $this->processRecordCondition($relation['recordCondition'] ?? null);
            $tableConfig->addFlexFieldMultipleForeignTablesRelation($flexSheet, $flexField, $allowedTables, $recordCondition);
        }
    }

    private function addForeignParentRelations(TableConfig $tableConfig, array $foreignParentRelations): void {
        foreach ($foreignParentRelations as $relation) {
            $table = $relation['table'] ?? '';
            $column = $relation['column'] ?? '';
            if (empty($table) || empty($column)) {
                continue;
            }
            $recordCondition = $this->processRecordCondition($relation['recordCondition'] ?? null);
            $tableConfig->addForeignParentRelation($column, $table, $recordCondition);
        }
    }


    private function processRecordCondition(?array $recordConditionData): ?RecordConditionInterface {
        if (empty($recordConditionData)) {
            return null;
        }
        $recordCondition = $this->configTypeRegistry->create($recordConditionData['__type__'] ?? '', $recordConditionData['arguments'] ?? []);
        if (!($recordCondition instanceof RecordConditionInterface)) {
            throw new \InvalidArgumentException('Invalid record condition.');
        }
        return $recordCondition;
    }

    private function createTableConfig(array $tableConfigData): TableConfig {
        $table = $tableConfigData['table'] ?? '';
        $primaryColumn = $tableConfigData['primaryColumn'] ?? 'uid';
        if (empty($table)) {
            throw new \InvalidArgumentException('Invalid table config: missing tableName.');
        }
        if (empty($primaryColumn)) {
            throw new \InvalidArgumentException('Invalid table config: missing primaryColumn for table ' . $table . '.');
        }
        return new TableConfig(
            $table,
            $primaryColumn,
            $tableConfigData['explicitSelectQuery'] ?? '',
            $tableConfigData['l10nParentColumn'] ?? 'l10n_parent',
            $tableConfigData['languageColumn'] ?? 'sys_language_uid',
            $tableConfigData['pidColumn'] ?? '',
            $tableConfigData['includeParents'] ?? false,
            $tableConfigData['flexFormColumn'] ?? 'pi_flexform'
        );
    }
}