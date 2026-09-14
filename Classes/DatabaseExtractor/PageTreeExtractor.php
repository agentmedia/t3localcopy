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

    protected $rootPageUid;
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

    public function extract() {
        $exportedPageUids = $this->getTreeIds($this->rootPageUid);
        $this->insertQueries = [];
        foreach ($exportedPageUids as $pageUid) {
            // This should do the whole database extraction
            $tableExtractor = new TableExtractor($this->pdo, $this->insertCollector, $this->pagesTableConfig, $this->tableConfigRegistry);
            $tableExtractor->collectInsertQueries($pageUid);
        }
    }

    // protected function getParentIds($pageId)
    // {
    //     $parentIds = [];
    //     $currentId = $pageId;
    //     while ($currentId) {
    //         $currentId = $this->getParentId($currentId);
    //         if ($currentId) {
    //             $parentIds[] = $currentId;
    //         }
    //     }
    //     return array_reverse($parentIds);
    // }

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

    // protected function getParentId($pageId)
    // {
    //     $selectQuery = $this->pdo->prepare('SELECT pid FROM ' . $this->tableName . ' WHERE ' . $this->languageColumn . ' = :langUid AND uid = :uid');
    //     $selectQuery->execute(['langUid' => 0, 'uid' => $pageId]);
    //     $result = $selectQuery->fetch(\PDO::FETCH_ASSOC);
    //     return $result ? (int) $result['pid'] : null;
    // }

    // protected function getLanguageOverlayPageUids($defaultLanguagePageUid)
    // {
    //     $selectQuery = $this->pdo->prepare('SELECT ' . $this->primaryColumn . ' FROM ' . $this->tableName . ' WHERE ' . $this->languageColumn . ' != :langUid AND ' . $this->l10nParentColumn . ' = :l10nParent');
    //     $selectQuery->execute(['langUid' => 0, 'l10nParent' => $defaultLanguagePageUid]);
    //     $result = $selectQuery->fetchAll(\PDO::FETCH_ASSOC);
    //     return array_column($result, $this->primaryColumn);
    // }
}