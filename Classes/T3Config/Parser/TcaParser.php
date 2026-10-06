<?php

namespace AgentMedia\T3LocalCopy\T3Config\Parser;

final class TcaParser extends TcaLikeParserAbstract {

    private array $tca; 

    /**
     * Constructor for the TcaParser.
     *
     * @param array $tca The TCA configuration array.
     */
    public function __construct(array $tca) {
        $this->tca = $tca;
    }

    /**
     * 
     * Creates a TcaParser instance from a PHP file containing TCA configuration.
     * 
     * Note that this method may fail when called in a non-typo3 context, in case the TCA contains Typo3 classes or functions that are not available.
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

    /**
     * Gets the configuration fields that match the specified criteria.
     *
     * @param array $matchConfig The criteria to match against the TCA fields.
     * @return array The configuration fields that match the specified criteria.
     */

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
}