<?php
namespace AgentMedia\T3LocalCopy\Export;
use AgentMedia\T3LocalCopy\EventHandling\EventListenerInterface;
use AgentMedia\T3LocalCopy\DatabaseExtractor\TableExtractor;

final class ExportInterruptionListener implements EventListenerInterface {
    public function handleEvent(array $args = []): void {
        // Handle the interrupt event
        TableExtractor::$extractionInterrupted = true;
    }
}