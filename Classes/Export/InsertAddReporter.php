<?php

namespace AgentMedia\T3LocalCopy\Export;

use AgentMedia\T3LocalCopy\EventHandling\EventListenerInterface;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;


final class InsertAddReporter implements EventListenerInterface
{
    const VERBOSITY_NONE = 0;
    const VERBOSITY_LOW = 1;
    const VERBOSITY_MEDIUM = 2;
    const VERBOSITY_HIGH = 3;


    const VERBOSITY_ALL = 4;
    private int $verbosityLevel;

    private $numberOfEvents = 0;
    public function getVerbosityLevels(): array
    {
        return [
            self::VERBOSITY_LOW,
            self::VERBOSITY_MEDIUM,
            self::VERBOSITY_HIGH,
        ];
    }

    private $eventHandler;
    function __construct(int $verbosityLevel)
    {
        if (!in_array($verbosityLevel, $this->getVerbosityLevels())) {
            throw new \InvalidArgumentException("Invalid verbosity level: $verbosityLevel");
        }
        $this->verbosityLevel = $verbosityLevel;
    }

    // Class implementation goes here
    public function handleEvent(array $args = []): void
    {
        $this->numberOfEvents++;
        if ($this->isReportRequired()) {
            /**
             * @var string $tableName
             */
            $tableName = $args['tableName'];
            /**
             * @var array $data
             */
            $data = $args['data'];
            /**
             * @var TableConfig $tableConfig
             */
            $tableConfig = $args['tableConfig'];
            $uid = $data[$tableConfig->getPrimaryColumn()] ?? '<not set>';

            $message = "Collected insert number: " . $this->numberOfEvents . ", table: " . $tableName . ", uid: " . $uid;
            if ($this->verbosityLevel >= self::VERBOSITY_MEDIUM) {
                $message .= ", data: " . json_encode($data);
            }
            echo $message . "\n";
        }
    }

    private function isReportRequired(): bool
    {
        switch ($this->verbosityLevel) {
            case self::VERBOSITY_LOW:
                return $this->numberOfEvents % 1000 === 0;
            case self::VERBOSITY_MEDIUM:
                return $this->numberOfEvents % 100 === 0;
            case self::VERBOSITY_HIGH:
                return $this->numberOfEvents % 10 === 0;
            case self::VERBOSITY_ALL:
                return true;
            default:
                return false;
        }
    }
}