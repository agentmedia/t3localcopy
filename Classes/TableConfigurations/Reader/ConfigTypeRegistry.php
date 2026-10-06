<?php

namespace AgentMedia\T3LocalCopy\TableConfigurations\Reader;


class ConfigTypeRegistry {

    const TYPE_KEY = '__type__';
    private array $registry = [];

    /**
     * Registers a class name for a specific type.
     * @param string $className The class name to register, best taken by the ::class constant.
     * @param string $type The type identifier to associate with the class name when the configuration is parsed.
     * @throws \InvalidArgumentException Thrown if the class does not exist.
     * @return void
     */
    public function register(string $className, string $type) {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Class $className does not exist.");
        }

        $this->registry[$type] = $className;
    }

    /**
     * Creates an instance of the class associated with the given type, recursively evaluating any nested type configurations.
     * @param string $type The type identifier for which to create an instance.
     * @param array $arguments The arguments to pass to the class constructor, can include nested type configurations.
     * @throws \InvalidArgumentException Thrown if no class is registered for the given type.
     * @return mixed An instance of the class associated with the given type.
     */
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
