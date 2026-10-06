<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Condition;
/**
 * This interface defines a contract for conditions that can be evaluated against a record.
 * Implementing classes should provide the logic for the isFullfilled method to determine if a record meets the condition.  
 */ 
interface RecordConditionInterface {
    public function isFullfilled(array $record): bool;
}