<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Condition;

interface RecordConditionInterface {
    public function isFullfilled($record): bool;
}