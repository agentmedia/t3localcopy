<?php
namespace AgentMedia\T3LocalCopy\TableConfigurations;


class TableConfigRegistry {
    protected array $tableConfigs = [];

    public function registerTableConfig(TableConfig $tableConfig) {
        $this->tableConfigs[$tableConfig->getTableName()] = $tableConfig;
    }

    public function getTableConfig(string $tableName): ?TableConfig {
        return $this->tableConfigs[$tableName] ?? null;
    }

    public function getAllTableConfigs(): array {
        return $this->tableConfigs;
    }

}