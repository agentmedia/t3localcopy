<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Condition;
use AgentMedia\T3LocalCopy\DatabaseExtractor\PageTreeExtractor;

/**
 * This condition checks if a given page record is part of the current rootline, defined by the currently executed PageTreeExtractor.
 * 
 * This condition is only applicable to page records.
 */
class PageInRootlineCondition implements RecordConditionInterface {
    
    /**
     * Empty constructor, needs to be defined with one argumant of type array
     * @param array $args Constructor argument, currently not used.
     */
    public function __construct(array $args = []) {
    }

    
    /**
     * Checks if the given page record is part of the current rootline.
     * This method expects the record to be from the 'pages' table and to have a 'uid' field.
     * @param mixed $record The page record to check, expected to be an array with at least a 'uid' field.
     * @return bool Returns true if the page record is part of the current rootline, false otherwise.
     */
    public function isFullfilled(array $record): bool {
        return in_array($record['uid'], PageTreeExtractor::getCurrentRootLine());
    }
}