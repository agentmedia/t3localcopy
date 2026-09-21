<?php

namespace AgentMedia\T3LocalCopy\T3Config\Parser;

use AgentMedia\T3LocalCopy\T3Config\Parser\TcaLikeParserInterface;

abstract class TcaLikeParserAbstract implements TcaLikeParserInterface {
     public function getConfigGroupDbFields(): array {
        return $this->getConfigFields([
            'type' => 'group',
            'internal_type' => 'db'
        ]);
    }

    public function getConfigSelectTableFields(): array {
        $selectFields = $this->getConfigFields([
            'type' => 'select',
            'foreign_table' => '*',
        ]);
        return $selectFields;
    }

    public function getConfigFalReferenceFields(): array {
       $oldFashionedFields = $this->getConfigFields([
           'type' => 'inline',
           'foreign_table' => 'sys_file_reference'
       ]);

       $typeFileFields = $this->getConfigFields([
           'type' => 'file'
       ]);

       return array_merge_recursive($oldFashionedFields, $typeFileFields);

    }
    
    protected function checkMatch(array $fieldDef, array $matchConfig): bool {
        foreach ($matchConfig as $configKey => $configValue) {
            if ($configValue === '*') {
                if (!isset($fieldDef['config'][$configKey])) {
                    return false;
                }
            } else {
                $value = $fieldDef['config'][$configKey] ?? null;
                if ($value !== $configValue) {
                    return false;
                }
            }
        }
        return true;
    }

}