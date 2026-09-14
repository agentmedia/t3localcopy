<?php

namespace AgentMedia\T3LocalCopy\FileCollecting;

use AgentMedia\T3LocalCopy\TableConfigurations\TableConfig;
use AgentMedia\T3LocalCopy\EventHandling\EventListenerInterface;

class FileCollectListener implements EventListenerInterface {

    private $collectedFiles = [];
    private TableConfig $fileTableConfig;
    private TableConfig $storageTableConfig;

    private $pdo; 

    public function __construct(\PDO $pdo, TableConfig $fileTableConfig, TableConfig $storageTableConfig) {
        $this->pdo = $pdo;
        $this->fileTableConfig = $fileTableConfig;
        $this->storageTableConfig = $storageTableConfig;
    }
    public function handleEvent(array $params) {
        $tableName = $params['tableName'];
        $data = $params['data'];
        
        // Implement the event handling logic here
        if ($tableName !== $this->fileTableConfig->getTableName()) {
            return;
        }

        // Calculate the complete file path 
        $filePath = $this->getFullPath($data);
        if ($filePath) {
            // Implement the logic to collect or process the file here
            $this->collectedFiles[] = $filePath;
        }
    }

    protected function getFullPath(array $data): string {
        $storageUid = $data['storage'] ?? '';
        $identifier = $data['identifier'] ?? '';
        if (!$storageUid || !$identifier) {
            return $identifier;
        }
        $stmt = $this->pdo->prepare('SELECT `configuration` FROM ' . $this->storageTableConfig->getTableName() . ' WHERE ' . $this->storageTableConfig->getPrimaryColumn() . ' = :storageUid');
        $stmt->execute(['storageUid' => $storageUid]);
        $storageRecord = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$storageRecord) {
            return $identifier;
        }

        $configDom = new \DOMDocument();
        $configDom->loadXML($storageRecord['configuration']);

        $xpath = new \DOMXpath($configDom);
        $elements = $xpath->query("//*[@index='basePath']/value");
        $pathEl = $elements->length > 0 ? $elements->item(0) : null;
        $basePath = $pathEl ? $pathEl->textContent : '';
        return rtrim($basePath, '/') . '/' . ltrim($identifier, '/');
    }

    public function getCollectedFiles(): array {
        return $this->collectedFiles;
    }
}