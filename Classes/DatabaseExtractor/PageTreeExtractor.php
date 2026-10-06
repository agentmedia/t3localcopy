<?php

namespace AgentMedia\T3LocalCopy\DatabaseExtractor;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfigRegistry;
use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;

class PageTreeExtractor
{
    protected ?TableConfig $pagesTableConfig;

    protected InsertCollector $insertCollector;
    protected TableConfigRegistry $tableConfigRegistry;
    protected \PDO $pdo;

    protected string $tableName;
    protected string $primaryColumn;

    protected string $languageColumn;

    protected string $l10nParentColumn;

    protected array $insertQueries = [];

    protected static $currentRootLine = [];

    protected $rootPageUid;

    /**
     * Constructs a PageTreeExtractor instance.
     *
     * @param int|string $rootPageUid The UID of the root page to start the extraction from.
     * @param \PDO $pdo The PDO instance for database access.
     * @param InsertCollector $insertCollector The insert collector for collecting insert queries.
     * @param TableConfigRegistry $tableConfigRegistry The table configuration registry.
     */
    public function __construct($rootPageUid, \PDO $pdo, InsertCollector $insertCollector, TableConfigRegistry $tableConfigRegistry)
    {
        $this->pdo = $pdo;
        $this->insertCollector = $insertCollector;
        $this->tableConfigRegistry = $tableConfigRegistry;
        $this->pagesTableConfig = $tableConfigRegistry->getTableConfig('pages');
        if (!$this->pagesTableConfig) {
            throw new \RuntimeException('Configuration for table \'pages\' not found.');
        }
        $this->tableName = $this->pagesTableConfig->getTableName();
        $this->primaryColumn = $this->pagesTableConfig->getPrimaryColumn();
        $this->languageColumn = $this->pagesTableConfig->getLanguageColumn();
        $this->l10nParentColumn = $this->pagesTableConfig->getL10nParentColumn();
        $this->rootPageUid = $rootPageUid;
    }

    /**
     * Gets the current root line of the page tree, defined during the extraction process.
     *
     * @return array The array of page UIDs representing the current root line.
     */
    public static function getCurrentRootLine(): array {
        return self::$currentRootLine;
    }

    /**
     * Extracts the page tree starting from the root page UID and collects insert queries for each page.
     *
     * @return void
     */
    public function extract() {
        $exportedPageUids = $this->getTreeIds($this->rootPageUid);
        self::$currentRootLine = $exportedPageUids;
        $this->insertQueries = [];
        foreach ($exportedPageUids as $pageUid) {
            // This should do the whole database extraction
            $tableExtractor = new TableExtractor($this->pdo, $this->insertCollector, $this->pagesTableConfig, $this->tableConfigRegistry);
            $tableExtractor->collectInsertQueries($pageUid);
        }
    }


    protected function getTreeIds($rootPageId)
    {
        $treeIds = [$rootPageId];
        $children = $this->getChildrenIds($rootPageId);
        foreach ($children as $childId) {
            $treeIds = array_merge($treeIds, $this->getTreeIds($childId));
        }
        return $treeIds;
    }

    protected function getChildrenIds($pageId)
    {
        $selectQuery = $this->pdo->prepare('SELECT ' . $this->primaryColumn . ' FROM ' . $this->tableName . ' WHERE ' . $this->languageColumn . ' = :langUid AND pid = :pid');
        $selectQuery->execute(['langUid' => 0, 'pid' => $pageId]);
        $result = $selectQuery->fetchAll(\PDO::FETCH_ASSOC);
        return array_column($result, $this->primaryColumn);
    }
}