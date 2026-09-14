<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Reader;

use AgentMedia\T3LocalCopy\Config\Condition\RecordConditionInterface;

class ConfigTypeRegistry {

    const TYPE_KEY = '__type__';
    private array $registry = [];

    public function register(string $className, string $type) {
        if (!class_exists($className) || !is_subclass_of($className, RecordConditionInterface::class)) {
            throw new \InvalidArgumentException("Class $className does not exist or does not implement RecordConditionInterface.");
        }

        $this->registry[$type] = $className;
    }

    public function create(string $type, array $arguments = []): mixed {
        $className = $this->registry[$type] ?? null;
        if ($className === null) {
            throw new \InvalidArgumentException("No class registered for type $type.");
        }
        $evaluatedArguments = [];
        foreach ($arguments as $arg) {
            if (is_array($arg) && isset($arg[self::TYPE_KEY])) {
                $type = $arg[self::TYPE_KEY];
                $argumentsForType = $arg['arguments'] ?? [];
                $evaluatedArguments[] = $this->create($type, $argumentsForType);
            } else {
                $evaluatedArguments[] = $arg;
            }

        }
        return new $className(...$evaluatedArguments);
    }
}
