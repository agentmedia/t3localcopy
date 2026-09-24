<?php 
namespace AgentMedia\T3LocalCopy\Export;

use AgentMedia\T3LocalCopy\DatabaseExtractor\InsertCollector;
use AgentMedia\T3LocalCopy\DatabaseExtractor\PageTreeExtractor;
use AgentMedia\T3LocalCopy\DatabaseExtractor\TableExtractor;
use AgentMedia\T3LocalCopy\EventHandling\EventHandler;
use AgentMedia\T3LocalCopy\EventHandling\EventListenerInterface;
use AgentMedia\T3LocalCopy\FileCollecting\FileCollectListener;
use AgentMedia\T3LocalCopy\TableConfigurations\Reader\ConfigReader;
use AgentMedia\T3LocalCopy\TableConfigurations\Reader\ConfigTypeRegistry;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfigRegistry;

class Exporter  {

    protected array $config;
    protected \PDO $pdo;

    protected ?InsertCollector $insertCollector = null;

    protected ?FileCollectListener $fileCollectListener = null;
    protected ?TableConfigRegistry $tableConfigRegistry = null;

    const EVENT_EXPORT_INTERRUPTED = 'exportInterrupted';

    protected ?ConfigTypeRegistry $configTypeRegistry;
    public function __construct(array $config, ?ConfigTypeRegistry $configTypeRegistry = null, int $verbosityLevel = InsertAddtListener::VERBOSITY_NONE) {
        if ($verbosityLevel !== InsertAddtListener::VERBOSITY_NONE) {
            EventHandler::addListener(TableExtractor::EVENT_INSERT_QUERY_ADDED, new InsertAddtListener($verbosityLevel));
        }
        EventHandler::addListener(self::EVENT_EXPORT_INTERRUPTED, new ExportInterruptionListener());
        $this->config = $config;
        $this->configTypeRegistry = $configTypeRegistry;
        $dbConf = $config['db'] ?? [];
        $this->pdo = new \PDO(
            $dbConf['dsn'] ?? '',
            $dbConf['username'] ?? '',
            $dbConf['password'] ?? '',
            [   
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        $configReader = new ConfigReader($this->configTypeRegistry);
        $this->tableConfigRegistry = $configReader->read($this->config);
    }

    public static function initCliInterruptHandling(): bool {
       if (!extension_loaded('pcntl')) {
        return false;
       }
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function () {
            echo "Export interrupted.\n";
            EventHandler::dispatchEvent(Exporter::EVENT_EXPORT_INTERRUPTED);
            exit;
        });

        pcntl_signal(SIGTERM, function () {
            echo "Export interrupted.\n";
            EventHandler::dispatchEvent(Exporter::EVENT_EXPORT_INTERRUPTED);
            exit;
        });
        return true;
    }
    /**
     * Gets the table configuration registry. Allows to modify or inspect the table configurations before executing the export.
     * 
     * @return TableConfigRegistry|null
     */
    public function getTableConfigRegistry(): ?TableConfigRegistry {
        return $this->tableConfigRegistry;
    }

    public function execute(): void {
        
        $this->insertCollector = new InsertCollector($this->pdo);
        $this->performExplicitSelects($this->tableConfigRegistry);
        $commonConfig = $this->config['common'] ?? [];
        $noFiles = $commonConfig['noFiles'] ?? false;
        if (!$noFiles) {
            $fileConfig = $this->tableConfigRegistry->getTableConfig('sys_file');
            $storageConfig = $this->tableConfigRegistry->getTableConfig('sys_file_storage');
            if (!$fileConfig || !$storageConfig) {
                throw new \LogicException('Missing configuration for sys_file or sys_file_storage table. Either add these configurations or set noFiles to true in the common configuration.');
            }
            $this->fileCollectListener = new FileCollectListener($this->pdo, $fileConfig, $storageConfig);
            EventHandler::addListener(TableExtractor::EVENT_INSERT_QUERY_ADDED, $this->fileCollectListener);
        }
        $rootPageUid = $commonConfig['rootPageUid'] ?? null;
        if (!$rootPageUid) {
            throw new \LogicException('Missing rootPageUid in the common configuration.');
        }
        $pageTreeExtractor = new PageTreeExtractor($rootPageUid, $this->pdo, $this->insertCollector, $this->tableConfigRegistry);
        $pageTreeExtractor->extract();
    }

    protected function performExplicitSelects(TableConfigRegistry $tableConfigRegistry) {
        $tableConfigs = $tableConfigRegistry->getAllTableConfigs();
        foreach ($tableConfigs as $tableName => $tableConfig) {
            /**
             * @var TableConfig $tableConfig
             */
            $explicitSelectQuery = $tableConfig->getExplicitSelectQuery();
            if ($explicitSelectQuery) {
                $select = "SELECT * FROM " . $tableName . " WHERE " . $explicitSelectQuery;
                $stmt = $this->pdo->query($select);
                $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($results as $row) {
                    $this->insertCollector->addInsert($tableName, $row, $tableConfig->getPrimaryColumn());
                }
            }
        }
    }

    public function getInsertsSql(): string {
        return $this->insertCollector ? $this->insertCollector->getInsertsString() : '';
    }

    public function getCollectedFiles(): array {
        return $this->fileCollectListener ? $this->fileCollectListener->getCollectedFiles() : [];
    }

}