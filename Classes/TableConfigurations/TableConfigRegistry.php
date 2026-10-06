<?php
namespace AgentMedia\T3LocalCopy\TableConfigurations;


class TableConfigRegistry {
    /**
     * Registry for table configurations.
     *
     * @var array<string, TableConfig>
     */
    protected array $tableConfigs = [];

    /**
     * Registers a table configuration.
     *
     * @param TableConfig $tableConfig The table configuration to register.
     * @return void
     */
    public function registerTableConfig(TableConfig $tableConfig) {
        $this->tableConfigs[$tableConfig->getTableName()] = $tableConfig;
    }

    /**
     * Retrieves a table configuration by its table name.
     *
     * @param string $tableName The name of the table.
     * @return TableConfig|null The table configuration if found, or null otherwise.
     */
    public function getTableConfig(string $tableName): ?TableConfig {
        return $this->tableConfigs[$tableName] ?? null;
    }

    /**
     * Retrieves all registered table configurations.
     *
     * @return array<string, TableConfig> An array of all registered table configurations, keyed by table name.
     */
    public function getAllTableConfigs(): array {
        return $this->tableConfigs;
    }

}