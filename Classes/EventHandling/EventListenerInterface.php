<?php
namespace AgentMedia\T3LocalCopy\EventHandling;

interface EventListenerInterface {
 public function handleEvent(array $params);
}