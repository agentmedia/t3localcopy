<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Condition;
use AgentMedia\T3LocalCopy\DatabaseExtractor\PageTreeExtractor;

class PageInRootlineCondition implements RecordConditionInterface {
    
    public function __construct(array $args = []) {
    }

    
    
    public function isFullfilled($record): bool {
        return in_array($record['uid'], PageTreeExtractor::getCurrentRootLine());
    }
}