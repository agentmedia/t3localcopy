<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Condition;


class DirectRecordCondition implements RecordConditionInterface {
    protected array $desiredColumnValues;

    public function __construct(array $desiredColumnValues) {
        $this->desiredColumnValues = $desiredColumnValues;
    }
    
    public function isFullfilled($record): bool {
        foreach ($this->desiredColumnValues as $column => $value) {
            if (!isset($record[$column]) || $record[$column] != $value) {
                return false;
            }
        }
        return true;
    }
}