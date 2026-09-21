<?php

namespace AgentMedia\T3LocalCopy\T3Config\Parser;

final class TcaParser extends TcaLikeParserAbstract {

    private array $tca; 

    public function __construct(array $tca) {
        $this->tca = $tca;
    }

    /**
     * 
     * Creates a TcaParser instance from a PHP file containing TCA configuration.
     * 
     * @param string $filePath The path to the PHP file containing the TCA configuration.
     * @param string $forTable The table name for which the TCA configuration is intended. If empty, it will be inferred from the file name.
     * @throws \Exception Raises an exception if the PHP file does not exist, is not readable, or does not return a valid array.
     * @return TcaParser Returns the created TcaParser instance.
     */
    public static function fromPhpFile(string $filePath, string $forTable = ''): TcaParser {
        //if  forTable is empty, we take it from the file name
        if (empty($forTable)) {
            $forTable = pathinfo($filePath, PATHINFO_FILENAME);
        }
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("TCA configuration file not found or not readable: " . $filePath);
        }
        $tca = include $filePath;
        if (!is_array($tca)) {
            throw new \Exception("TCA configuration file did not return an array: " . $filePath);
        }
        return new self($tca);
    }


    public function getConfigFields(array $matchConfig): array {
        $result = [];
        if (!isset($this->tca['columns']) || !is_array($this->tca['columns'])) {
            return [];
        }
        foreach ($this->tca['columns'] as $fieldName => $fieldDef) {
                if ($this->checkMatch($fieldDef, $matchConfig)) {
                    $result[$fieldName] = [
                        'config' => $fieldDef['config'] ?? []
                    ];
                }
            }
        return $result;
    }
    // Implementation of TcaParser goes here
}