<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Condition;

/**
 *
 * This condition checks if a given table record matches the specified column values, directly without relations to any other tables.
 */
class DirectRecordCondition implements RecordConditionInterface {
    protected array $desiredColumnValues;

    /**
     * Constructor for DirectRecordCondition.
     * @param array $desiredColumnValues An associative array of column names and their desired values.
     */
    public function __construct(array $desiredColumnValues) {
        $this->desiredColumnValues = $desiredColumnValues;
    }
    /**
     * Checks if the given record matches the desired column values. The comparison is NOT type-strict (uses != instead of !==).
     * @param array $record The record to check.
     * @return bool Returns true if the record matches all desired column values, false otherwise.
     */
    public function isFullfilled(array $record): bool {
        foreach ($this->desiredColumnValues as $column => $value) {
            if (!isset($record[$column]) || $record[$column] != $value) {
                return false;
            }
        }
        return true;
    }
}