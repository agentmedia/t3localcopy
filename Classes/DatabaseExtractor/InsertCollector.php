<?php
namespace AgentMedia\T3LocalCopy\DatabaseExtractor;

use AgentMedia\T3LocalCopy\TableConfigurations\TableConfigRegistry;

final class InsertCollector
{
    public const DUMP_TYPE_INSERTS = 'inserts';
    public const DUMP_TYPE_UPSERTS = 'upserts';


    private bool $finalized = false;
    private array $inserts = [];

    private \PDO $pdo;

    private array $primaryColumns = [];

    private $continuousDumpFileHandle;
    private int $continuousChunkSize = 10000;
    private string $dumpType = self::DUMP_TYPE_UPSERTS;

    private array $dumpedInserts = [];

    private $numCurrentInserts = 0;
    public function __construct(\PDO $pdo, string $dumpType = self::DUMP_TYPE_UPSERTS)
    {
        $this->pdo = $pdo;
        $this->dumpType = $dumpType;
    }

    /**
     * 
     * Finalizes the insert collection by dumping any remaining inserts if required.
     * @return void
     */
    public function finalize(): void {
        if (!$this->finalized) {
            $this->dumpIfRequired(true);
            $this->finalized = true;
        }
    }

    private function dumpIfRequired(bool $force = false): bool {
        if ($this->continuousDumpFileHandle && ($this->numCurrentInserts >= $this->continuousChunkSize || $force)) {
            $sqlString = $this->getSqlString($this->continuousChunkSize, false);
            if ($sqlString) {
                fwrite($this->continuousDumpFileHandle, $sqlString);
                return true;
            }
        }
        return false;
    }
    /**
     * 
     * Enables continuous dumps of inserts to a specified file handle in chunks.
     * 
     * @param mixed $fileHandle
     * @param int $continuousChunkSize
     * @return void
     */
    public function enableContinuousDumps($fileHandle, int $continuousChunkSize = 10000) {
        $this->continuousDumpFileHandle = $fileHandle;
        $this->continuousChunkSize = $continuousChunkSize;;
    }

    public function hasInsert(string $table, $primaryKeyValue): bool
    {
        $dumpedPrimaryKeyValues = $this->dumpedInserts[$table] ?? [];
        return   isset($this->inserts[$table][$primaryKeyValue]) || \in_array($primaryKeyValue, $dumpedPrimaryKeyValues);
    }
    public function addInsert(string $table, array $data, $primaryKeyColumn): bool
    {
        $this->numCurrentInserts++;
        $this->primaryColumns[$table] = $primaryKeyColumn;

        if (!isset($this->inserts[$table])) {
            $this->inserts[$table] = [];
        }
        $primaryKeyValue = $data[$primaryKeyColumn] ?? null;
        if (!$primaryKeyValue) {
            throw new \InvalidArgumentException("Primary key value for column '$primaryKeyColumn' is missing in the provided data of table '$table'. Given: " . json_encode($data));
        }
        if (isset($this->inserts[$table][$primaryKeyValue])) {
            return false;
        }
        $this->inserts[$table][$primaryKeyValue] = $data;
        $this->dumpIfRequired();
        return true;
    }

    public function getInserts(): array
    {
        return $this->inserts;
    }

    public function getTables(): array
    {
        return array_keys($this->inserts);
    }

    public function getSqlString(int $chunkSize = 1000, bool $withTableComment = true): string {
        if ($this->dumpType === self::DUMP_TYPE_INSERTS) {
            return $this->getInsertsString($chunkSize, $withTableComment);
        } elseif ($this->dumpType === self::DUMP_TYPE_UPSERTS) {
            return $this->getUpsertsString($chunkSize, $withTableComment);
        }
        $this->inserts = [];
        $this->numCurrentInserts = 0;
        return '';
    }

    /**
     * This method iterates over all tables and generates the corresponding INSERT SQL statements in chunks.
     * @param int $chunkSize The number of rows to include in each INSERT statement chunk.
     * @param bool $withTableComment Whether to include table comments in the generated INSERT SQL string.
     * @return string Returns the generated INSERT SQL string for all tables.
     */
    private function getInsertsString(int $chunkSize = 1000, bool $withTableComment = true): string
    {
        $insertsString = '';
        $tables = $this->getTables();
        foreach ($tables as $table) {
            if ($withTableComment) {
                $insertsString .= "-- Inserts for table `$table`\n";
            }
            $insertsString .= $this->getTableInsertsString($table, $chunkSize);
        }
        return $insertsString;
    }

    /**
     * This method iterates over all tables and generates the corresponding UPSERT SQL statements in chunks.
     * @param int $chunkSize The number of rows to include in each UPSERT statement chunk.
     * @param bool $withTableComment Whether to include table comments in the generated UPSERT SQL string.
     * @return string Returns the generated UPSERT SQL string for all tables.
     */
    private function getUpsertsString(int $chunkSize = 1000, bool $withTableComment = true): string
    {
        $upsertsString = '';
        $tables = $this->getTables();
        foreach ($tables as $table) {
            if ($withTableComment) {
                $upsertsString .= "-- Upserts for table `$table`\n";
            }
            $upsertsString .= $this->getTableUpsertString($table, $chunkSize);
        }
        return $upsertsString;
    }

    /**
     * Generates an INSERT SQL string for the specified table.  
     * @param string $table The name of the table for which to generate the INSERT SQL string.
     * @param int $chunkSize The number of rows to include in each INSERT statement chunk.
     * @return string The generated INSERT SQL string for the specified table.
     */
    private function getTableInsertsString(string $table, int $chunkSize = 1000): string
    {
        $insertsString = '';
        if (!isset($this->inserts[$table])) {
            return $insertsString;
        }
        $rows = $this->inserts[$table];
        $chunks = array_chunk($rows, $chunkSize, true);
        foreach ($chunks as $uid => $chunk) {
            $this->dumpedInserts[$table] = $this->dumpedInserts[$table] ?? [];
            $this->dumpedInserts[$table][] = $uid;
            $values = [];
            foreach ($chunk as $row) {
                $escapedValues = array_map(fn($value) => is_null($value) ? 'NULL' : $this->pdo->quote((string) $value), $row);
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }
            if (!empty($values)) {
                $columns = array_keys(reset($chunk));
                // Add backticks around column names for safety
                $columns = array_map(fn($col) => "`$col`", $columns);
                $insertsString .= "INSERT INTO `$table` (" . implode(', ', $columns) . ")\nVALUES " . implode(",\n", $values) . ";\n";
            }
        }
        return $insertsString;
    }

    /**
     * Generates an UPSERT (INSERT ... ON DUPLICATE KEY UPDATE) SQL string for the specified table. 
     * @param string $table The name of the table for which to generate the UPSERT SQL string.
     * @param int $chunkSize The number of rows to include in each UPSERT statement chunk.
     * @return string The generated UPSERT SQL string for the specified table.
     */
    private function getTableUpsertString(string $table, int $chunkSize = 1000): string
    {
        $upsertsString = '';
        if (!isset($this->inserts[$table])) {
            return $upsertsString;
        }
        $primaryColumn = $this->primaryColumns[$table] ?? null;
        if (!$primaryColumn) {
            return $upsertsString;
        }
        $rows = $this->inserts[$table];
        $chunks = array_chunk($rows, $chunkSize, true);
        foreach ($chunks as $chunk) {
            $values = [];
            foreach ($chunk as $uid => $row) {
                $this->dumpedInserts[$table] = $this->dumpedInserts[$table] ?? [];
                $this->dumpedInserts[$table][] = $uid;
                $escapedValues = array_map(fn($value) => is_null($value) ? 'NULL' : $this->pdo->quote((string) $value), $row);
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }
            if (!empty($values)) {
                $columns = array_keys(reset($chunk));
                $colsWithoutPrimary = array_filter($columns, fn($col) => $col !== $primaryColumn);
                // Add backticks around column names for safety
                $columns = array_map(fn($col) => "`$col`", $columns);
                $colsWithoutPrimary = array_map(fn($col) => "`$col`", $colsWithoutPrimary);
                $upsertsString .= "INSERT INTO `$table` (" . implode(', ', $columns) . ")\nVALUES " . implode(",\n", $values);

                if ($primaryColumn && !empty($colsWithoutPrimary)) {
                    $updates = array_map(fn($col) => "$col = VALUES($col)", $colsWithoutPrimary);
                    $upsertsString .= "\nON DUPLICATE KEY UPDATE " . implode(', ', $updates);
                }
                $upsertsString .= ";\n";
            }
        }
        return $upsertsString;
    }
}