<?php

namespace AgentMedia\T3LocalCopy\T3Config\Utility;

use AgentMedia\T3LocalCopy\T3Config\Parser\FlexFormParser;
use AgentMedia\T3LocalCopy\T3Config\Parser\TcaLikeParserAbstract;
use AgentMedia\T3LocalCopy\T3Config\Parser\TcaParser;
use AgentMedia\T3LocalCopy\TableConfigurations\Condition\RecordConditionInterface;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;

final class AutoConfigurator {

    public static function addFlexConfigFalRelations(TableConfig $tableConfig, FlexFormParser $flexFormParser, RecordConditionInterface $recordCondition, array $excludeSheetFields = []) {
        $falFields = $flexFormParser->getConfigFalReferenceFields();
        foreach ($falFields as $sheetName => $fields) {
            foreach ($fields as $fieldName => $fieldConfig) {
                if (isset($excludeSheetFields[$sheetName]) && in_array($fieldName, $excludeSheetFields[$sheetName])) {
                    continue;
                }
                $foreignTable = $fieldConfig['config']['foreign_table'] ?? 'sys_file_reference';
                
                $tableConfig->addForeignChildRelation('uid_foreign', $foreignTable, ['fieldname' => $fieldName, 'tablenames' => $tableConfig->getTableName()], $recordCondition);
            }
        }
    }

    public static function addTcaConfigFalRelations(TableConfig $tableConfig, TcaParser $tcaParser, ?RecordConditionInterface $recordCondition = null, array $excludeFields = []) {
        $falFields = $tcaParser->getConfigFalReferenceFields() ?? [];
        foreach ($falFields as $fieldName => $fieldConfig) {
                if (in_array($fieldName, $excludeFields)) {
                    continue;
                }
                $foreignTable = $fieldConfig['config']['foreign_table'] ?? 'sys_file_reference';
                if (empty($foreignTable)) {
                    continue;
                }
                $tableConfig->addForeignChildRelation('uid_foreign', $foreignTable, ['fieldname' => $fieldName, 'tablenames' => $tableConfig->getTableName()], $recordCondition);
        }
    }


    /**
     * 
     * Adds TCA configuration for group DB fields relations.
     * 
     * @param TableConfig $tableConfig The table configuration object to which the group DB fields relations will be added.
     * @param TcaLikeParserAbstract $flexFormParser The TCA-like parser instance used to extract group DB fields configuration.
     * @param RecordConditionInterface $recordCondition The record condition used to determine when the relation should be applied.
     * @param array $excludeSheetFields An array of sheet names and field names to be excluded from the configuration, f.e. ['sheet1' => ['field1', 'field2']] will include all fields except the specified ones. If you use a TcaParser "sheet" refers to the table name (a real sheet only exists for flex forms).
     * @return void
     */
    public static function addFlexConfigGroupDbFieldsRelations(TableConfig $tableConfig, FlexFormParser $flexFormParser, RecordConditionInterface $recordCondition, array $excludeSheetFields = []) {
        $groupDbFields = $flexFormParser->getConfigGroupDbFields();
        foreach ($groupDbFields as $sheetName => $fields) {
            foreach ($fields as $fieldName => $fieldConfig) {
                if (isset($excludeSheetFields[$sheetName]) && in_array($fieldName, $excludeSheetFields[$sheetName])) {
                    continue;
                }
                $commaSeparatedAllowedTables = $fieldConfig['config']['allowed'] ?? '';
                if (empty($commaSeparatedAllowedTables)) {
                    continue;
                }
                $allowedTables = array_map('trim', explode(',', $commaSeparatedAllowedTables));
                if (empty($allowedTables)) {
                    continue;
                }
                if (count($allowedTables) === 1) {
                    $tableConfig->addFlexFieldForeignTableRelation($sheetName, $fieldName, $allowedTables[0], $recordCondition);
                } else {
                    $tableConfig->addFlexFieldMultipleForeignTablesRelation($sheetName, $fieldName, $allowedTables, $recordCondition);
                }
            }
        }
    }

    public static function addFlexConfigSelectTableRelations(TableConfig $tableConfig, FlexFormParser $flexFormParser, RecordConditionInterface $recordCondition, array $excludeSheetFields = []) {
        $selectTableFields = $flexFormParser->getConfigSelectTableFields();
        foreach ($selectTableFields as $sheetName => $fields) {
            foreach ($fields as $fieldName => $fieldDef) {
                if (isset($excludeSheetFields[$sheetName]) && in_array($fieldName, $excludeSheetFields[$sheetName])) {
                    continue;
                }
                $foreignTable = $fieldDef['config']['foreign_table'] ?? '';
                if (empty($foreignTable)) {
                    continue;
                
                }
                $tableConfig->addFlexFieldForeignTableRelation($sheetName, $fieldName, $foreignTable, $recordCondition);
            }
        }
    }

    public static function addTcaConfigSelectTableRelations(TableConfig $tableConfig, TcaParser $tcaParser, ?RecordConditionInterface $recordCondition = null, array $excludeFields = []) {
        $selectTableFields = $tcaParser->getConfigSelectTableFields();
        foreach ($selectTableFields as $fieldName => $fieldDef) {
            if (in_array($fieldName, $excludeFields)) {
                continue;
            }
            $foreignTable = $fieldDef['config']['foreign_table'] ?? '';
            if (empty($foreignTable)) {
                continue;
            }
            $tableConfig->addUidsForeignTableRelation($fieldName, $foreignTable, $recordCondition);
        }
    }

    public static function addTcaConfigGroupDbFieldsRelations(TableConfig $tableConfig, TcaParser $tcaParser, ?RecordConditionInterface $recordCondition = null, array $excludeFields = []) {
        $groupDbFields = $tcaParser->getConfigGroupDbFields();
        foreach ($groupDbFields as $fieldName => $fieldConfig) {
            if (in_array($fieldName, $excludeFields)) {
                continue;
            }
            $commaSeparatedAllowedTables = $fieldConfig['config']['allowed'] ?? '';
            if (empty($commaSeparatedAllowedTables)) {
                continue;
            }
            $allowedTables = array_map('trim', explode(',', $commaSeparatedAllowedTables));
            if (empty($allowedTables)) {
                continue;
            }
            if (count($allowedTables) === 1) {
                $tableConfig->addUidsForeignTableRelation($fieldName, $allowedTables[0], $recordCondition);
            } else {
                $tableConfig->addUidsMultipleForeignTablesRelation($fieldName, $allowedTables, $recordCondition);
            }
        }
    }
}