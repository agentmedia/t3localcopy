<?php
namespace AgentMedia\T3LocalCopy\DatabaseExtractor;

use AgentMedia\T3LocalCopy\T3Config\Parser\FlexFormParser;
use AgentMedia\T3LocalCopy\TableConfigurations\Condition\RecordConditionInterface;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfigRegistry;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;
use AgentMedia\T3LocalCopy\EventHandling\EventHandler;

class TableExtractor {
    protected TableConfig $tableConfig;
    protected \PDO $pdo;
    protected TableConfigRegistry $tableConfigRegistry;
    protected bool $includeParent = false;
    protected InsertCollector $insertCollector;


    const EVENT_INSERT_QUERY_ADDED = 'TableExtractor__Insert_Query_Added';

    
    public function __construct(\PDO $pdo, InsertCollector $insertCollector, TableConfig $tableConfig, TableConfigRegistry $tableConfigRegistry) {
        $this->pdo = $pdo;
        $this->insertCollector = $insertCollector;
        $this->tableConfig  = $tableConfig;   
        $this->tableConfigRegistry = $tableConfigRegistry;
        $this->includeParent = $tableConfig->getIncludeParents();
        //$this->collectInsertQueries($uid);
    }


    protected function collectLanguageOverlayInsertQueries($defaultUid) {
        $languageColumn = $this->tableConfig->getLanguageColumn();
        $l10nParentColumn = $this->tableConfig->getL10nParentColumn();
        if (!$languageColumn || !$l10nParentColumn) {
            return;
        }
        $translations = $this->executeSelect(
            $this->tableConfig->getTableName(),
            "$l10nParentColumn = :defaultUid",
            ['defaultUid' => $defaultUid],
            'uid'
        );
        $translationUids = array_column($translations, 'uid');
        foreach ($translationUids as $translationUid) {
            $this->collectInsertQueries($translationUid);
        }
    }

    public function collectInsertQueries($uid): bool {
        if ($this->insertCollector->hasInsert($this->tableConfig->getTableName(), $uid)) {
            return false;
        }
        $primaryColumn = $this->tableConfig->getPrimaryColumn();
        $record = $this->addInsertQueryFromSelect($this->tableConfig->getTableName(), "$primaryColumn = :uid", ['uid' => $uid]);
        if ($record) {
            //$this->addParentRecord($record);
            $this->addUidsForeignTableRelations($record);
            $this->addUidsMultipleForeignTableRelations($record);
            $this->addFlexFieldForeignTableRelations($record);
            $this->addFlexFieldMultipleForeignTablesRelations($record);
            // $this->addTcaColumnForeignTableRelations($record);
            // $this->addTcaColumnMultipleForeignTableRelations($record);
            $this->addForeignChildRelations($record);
            if (!$this->tableConfig->getLanguageColumn() || (string)$record[$this->tableConfig->getLanguageColumn()] === '0') {
                $this->addParentRecord($record);
                $this->addForeignParentRelations($record);
                $this->collectLanguageOverlayInsertQueries($uid);

            }
        }
        return true;
    }

    // protected function addTcaColumnForeignTableRelations(array $thisRecord) {
    //     $relations = $this->tableConfig->getTcaColumnForeignTableRelations();
    //     foreach ($relations as $relation) {
    //         $allowedTables = [$relation['table']];
    //         $this->processTcaColumnForeignTablesRelation($thisRecord, $relation['tcaColumn'], $relation['recordCondition'], $allowedTables);
    //     }
    // }

    // protected function addTcaColumnMultipleForeignTableRelations(array $thisRecord) {
    //     $relations = $this->tableConfig->getTcaColumnMultipleForeignTableRelations();
    //     foreach ($relations as $relation) {
    //         $this->processTcaColumnForeignTablesRelation($thisRecord, $relation['tcaColumn'], $relation['recordCondition'], $relation['allowedTables']);
    //     }
    // }

    protected function addForeignParentRelations(array $thisRecord) {
        $relations = $this->tableConfig->getForeignParentRelations();
        foreach ($relations as $relation) {
            $recordCondition = $relation['recordCondition'];
            if ($recordCondition instanceof RecordConditionInterface && !$recordCondition->isFullfilled($thisRecord)) {
                continue;
            }
            $foreignTableConfig = $this->getProcessableForeignTableConfig($relation['table']);
            if (!$foreignTableConfig) {
                continue;
            }
            $columnValue = $thisRecord[$relation['column']] ?? null;
            if ($columnValue === null) {
                continue;
            }
            $foreignTableExtractor = new self($this->pdo, $this->insertCollector, $foreignTableConfig, $this->tableConfigRegistry);
            $foreignTableExtractor->collectInsertQueries($columnValue);
        }
    }

    protected function addParentRecord(array $thisRecord) {
        if (!$this->includeParent) {
            return;
        }
        // Only follow parent on default language records
        if ($this->tableConfig->getLanguageColumn() && $thisRecord[$this->tableConfig->getLanguageColumn()] > 0) {
            return;
        }
        $pidColumn = $this->tableConfig->getPidColumn();
        $parentUid = $thisRecord[$pidColumn] ?? null;
        if (!$parentUid) {
            return;
        }
        $parentTableExtractor = new self($this->pdo, $this->insertCollector, $this->tableConfig, $this->tableConfigRegistry);
        $parentTableExtractor->collectInsertQueries($parentUid);
    }

    protected function addFlexFieldForeignTableRelations(array $thisRecord) {
        $relations = $this->tableConfig->getFlexFieldForeignTableRelations();
        foreach ($relations as $relation) {
            $allowedTables = [$relation['table']];
            $this->processFlexFieldForeignTablesRelation($thisRecord, $relation['flexSheet'], $relation['flexField'], $relation['recordCondition'], $allowedTables);
        }
    }

    protected function addFlexFieldMultipleForeignTablesRelations(array $thisRecord) {
        $relations = $this->tableConfig->getFlexFieldMultipleForeignTableRelations();
        foreach ($relations as $relation) {
            $this->processFlexFieldForeignTablesRelation($thisRecord, $relation['flexSheet'], $relation['flexField'], $relation['recordCondition'], $relation['allowedTables']);
        }
    }
    protected function processFlexFieldForeignTablesRelation(array $thisRecord, string $flexSheet, string $flexField, ?RecordConditionInterface $recordCondition, array $allowedTables) {
        if ($recordCondition instanceof RecordConditionInterface && !$recordCondition->isFullfilled($thisRecord)) {
            return;
        }
        $flexForm = $thisRecord[$this->tableConfig->getFlexFormColumn()] ?? null;
        if (!$flexForm) {
            return;
        }
        $parser = FlexFormParser::fromXml($flexForm);
        $flexValue = $parser->getValue($flexSheet, $flexField);
        if (!$flexValue) {
            return;
        }
        $tableUids = array_map('trim', explode(',', $flexValue));
        if (empty($tableUids)) {
            return;
        }
        $this->processForeignTableUids($tableUids, $allowedTables);
    }

    protected function processTcaColumnForeignTablesRelation(array $thisRecord, string $tcaColumn, ?RecordConditionInterface $recordCondition, array $allowedTables) {
        if ($recordCondition instanceof RecordConditionInterface && !$recordCondition->isFullfilled($thisRecord)) {
            return;
        }
        $columnValue = $thisRecord[$tcaColumn] ?? null;
        if ($columnValue === null) {
            return;
        }
        $tableUids = array_map('trim', explode(',', $columnValue));
        if (empty($tableUids)) {
            return;
        }
        $this->processForeignTableUids($tableUids, $allowedTables);
    }

    protected function addUidsForeignTableRelations(array $thisRecord) {
        $relations = $this->tableConfig->getUidsForeignTableRelations();
        foreach ($relations as $relation) {
            $recordCondition = $relation['recordCondition'];
            if ($recordCondition instanceof RecordConditionInterface && !$recordCondition->isFullfilled($thisRecord)) {
                continue;
            }
            
            $columnValue = $thisRecord[$relation['column']] ?? null;
            if ($columnValue === null) {
                continue;
            }
            $tableUids = array_map('trim', explode(',', $columnValue));
            if (empty($tableUids)) {
                continue;
            }
            $this->processForeignTableUids($tableUids, [$relation['table']]);
        }
    }

    protected function addUidsMultipleForeignTableRelations(array $thisRecord) {
        $relations = $this->tableConfig->getUidsMultipleForeignTableRelations();
        foreach ($relations as $relation) {
            $recordCondition = $relation['recordCondition'] ?? null;
            if ($recordCondition instanceof RecordConditionInterface && !$recordCondition->isFullfilled($thisRecord)) {
                    continue;
            }
              
            $allowedTables = $relation['allowedTables'];
            $columnValue = $thisRecord[$relation['column']] ?? null;
            if ($columnValue === null) {
                continue;
            }
            $tableUids = array_map('trim', explode(',', $columnValue));
            $this->processForeignTableUids($tableUids, $allowedTables);
        }
    }

    protected function processForeignTableUids(array $tableUids, array $allowedTables) {
        foreach ($tableUids as $tableUid) {
                // separate at last underscore to get table name and uid
                $lastUnderscorePos = strrpos($tableUid, '_');
                if ($lastUnderscorePos === false) {
                    // This can happen if the TCA was modified, before only one table was allowed, now multiple. We then use the first allowedTable as fallback
                    $uid = $tableUid;
                    $table = $allowedTables[0];
                } else {
                    $table = substr($tableUid, 0, $lastUnderscorePos);
                    $uid = substr($tableUid, $lastUnderscorePos + 1);
                }
                // $table and $uid are already set correctly above, no need to override here
                
                if (!in_array($table, $allowedTables)) {
                    //TODO: log that the table is not allowed
                    continue;
                }
                $foreignTableConfig = $this->getProcessableForeignTableConfig($table);
                if (!$foreignTableConfig) {
                    continue;
                }
                $foreignTableExtractor = new self($this->pdo, $this->insertCollector, $foreignTableConfig, $this->tableConfigRegistry);
                $foreignTableExtractor->collectInsertQueries($uid);
        }
    }


    protected function addForeignChildRelations(array $thisRecord) {
        $relations = $this->tableConfig->getForeignChildRelations();
        foreach ($relations as $relation) {
            $recordCondition = $relation['recordCondition'] ?? null;
            if ($recordCondition instanceof RecordConditionInterface && !$recordCondition->isFullfilled($thisRecord)) {
                    continue;
            }
            $table = $relation['table'];
            $column = $relation['column'];
            $conditions = $relation['conditions'] ?? [];
            $whereQuery = $column . ' = :uid';
            $placeholderValues = ['uid' => $thisRecord[$this->tableConfig->getPrimaryColumn()] ?? ''];
            
            foreach ($conditions as $key => $value) {
                $whereQuery .= ' AND ' . $key . ' = :' . $key;
                $placeholderValues[$key] = $value;
            }
            


            $foreignTableConfig = $this->getProcessableForeignTableConfig($table);
            if (!$foreignTableConfig) {
                continue;
            }
            $uidData = $this->executeSelect($table, $whereQuery, $placeholderValues, $foreignTableConfig->getPrimaryColumn());
            $uids = array_column($uidData ?: [], $foreignTableConfig->getPrimaryColumn());
            foreach ($uids as $uid) {
                $foreignTableExtractor  = new self($this->pdo, $this->insertCollector, $foreignTableConfig, $this->tableConfigRegistry);
                $foreignTableExtractor->collectInsertQueries($uid);
            }
        }
    }
    protected function getProcessableForeignTableConfig($table): ?TableConfig {
       return  $this->tableConfigRegistry->getTableConfig($table);
       // $foreignTableConfig = $this->tableConfigRegistry->getTableConfig($table);
        // if (!$foreignTableConfig) {
        //     // Todo: log that the table is not configured
        //     return null;
        // }
        // return $foreignTableConfig->getExplicitSelectQuery() ? null : $foreignTableConfig;
    }

    protected function addInsertQueryFromSelect(string $tableName,string $whereQuery, array $placeholderValues): ?array {
        $data = $this->executeSelect($tableName, $whereQuery, $placeholderValues, '*');
        $firstRow = $data[0] ?? null;
        if ($firstRow && $this->insertCollector->addInsert($tableName, $firstRow, $this->tableConfig->getPrimaryColumn())) {
            EventHandler::dispatchEvent(self::EVENT_INSERT_QUERY_ADDED, ['tableName' => $tableName, 'data' => $firstRow, 'tableConfig' => $this->tableConfig]);
        }
        return $firstRow;
    }

    protected function executeSelect(string $tableName,string $whereQuery, array $placeholderValues, string $cols = '*'): array {
        $selectQuery = "SELECT " . $cols . " FROM " . $tableName . " WHERE " . $whereQuery;
        $stmt = $this->pdo->prepare($selectQuery);
        $stmt->execute($placeholderValues);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}