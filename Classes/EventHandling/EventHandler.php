<?php

namespace AgentMedia\T3LocalCopy\EventHandling;

final class EventHandler {
    protected static array $listeners = [];

    public static function addListener(string $name, EventListenerInterface $listener) {
        if (!isset(self::$listeners[$name])) {
            self::$listeners[$name] = [];
        }
        self::$listeners[$name][] = $listener;
    }

    public static function dispatchEvent(string $name, array $args = []) {
        if (isset(self::$listeners[$name])) {
            foreach (self::$listeners[$name] as $listener) {
                $listener->handleEvent($args);
            }
        }
    }
}