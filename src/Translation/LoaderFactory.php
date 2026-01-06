<?php

namespace Gravity\Translation;

use Gravity\Interfaces\LoaderStrategyInterface;
use Gravity\Translation\Loader\ArrayLoaderStrategy;
use Gravity\Translation\Loader\PhpLoaderStrategy;
use Gravity\Translation\Loader\YamlLoaderStrategy;

class LoaderFactory
{
    /** @var LoaderStrategyInterface[] */
    private array $strategies = [];

    /**
     * Register a loader strategy
     */
    public function registerStrategy(LoaderStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    /**
     * Get the appropriate loader for a resource
     * @param array<string, mixed>|string $resource
     * @throws \InvalidArgumentException if no loader supports the resource
     */
    public function getLoader(mixed $resource): LoaderStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($resource)) {
                return $strategy;
            }
        }

        $type = is_string($resource) ? "file: {$resource}" : gettype($resource);
        throw new \InvalidArgumentException("No loader found for resource type: {$type}");
    }

    /**
     * Create a factory with default loaders
     */
    public static function createDefault(): self
    {
        $factory = new self();
        $factory->registerStrategy(new ArrayLoaderStrategy());
        $factory->registerStrategy(new PhpLoaderStrategy());
        $factory->registerStrategy(new YamlLoaderStrategy());

        return $factory;
    }
}