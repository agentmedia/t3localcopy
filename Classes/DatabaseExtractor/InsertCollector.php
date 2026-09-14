<?php 
namespace AgentMedia\T3LocalCopy\DatabaseExtractor;

class InsertCollector
{
    protected array $inserts = [];

    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hasInsert(string $table, $primaryKeyValue): bool
    {
        return isset($this->inserts[$table][$primaryKeyValue]);
    }
    public function addInsert(string $table, array $data, $primaryKeyColumn): bool
    {
        if (!isset($this->inserts[$table])) {
            $this->inserts[$table] = [];
        }
        $primaryKeyValue = $data[$primaryKeyColumn] ?? null;
        if (!$primaryKeyValue) {
            throw new \InvalidArgumentException("Primary key value for column '$primaryKeyColumn' is missing in the provided data of table '$table'. Given: ".  json_encode($data));
        }
        if (isset($this->inserts[$table][$primaryKeyValue])) { 
            return false;
        }
        $this->inserts[$table][$primaryKeyValue] = $data;
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
    public function getInsertsString(int $chunkSize = 1000, bool $withTableComment = true): string
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

    public function getTableInsertsString(string $table, int $chunkSize = 1000): string
    {
        $insertsString = '';
        if (!isset($this->inserts[$table])) {
            return $insertsString;
        }
        $rows = $this->inserts[$table];
        $chunks = array_chunk($rows, $chunkSize, true);
        foreach ($chunks as $chunk) {
            $values = [];
            foreach ($chunk as $row) {
                $escapedValues = array_map(fn($value) => is_null($value) ? 'NULL' : $this->pdo->quote((string)$value), $row);
                $values[] = '(' . implode(', ', $escapedValues) . ')';
            }
            if (!empty($values)) {
                $insertsString .= "INSERT INTO `$table` (" . implode(', ', array_keys(reset($chunk))) . ") VALUES " . implode(",\n", $values) . ";\n";
            }
        }
        return $insertsString;
    }
}