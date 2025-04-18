<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\DTO;

/**
 * Collection of Rule DTOs
 */
class RuleCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<RuleDTO>
     */
    private array $rules;

    /**
     * @param RuleDTO ...$rules Rules to add to the collection
     */
    public function __construct(RuleDTO ...$rules)
    {
        $this->rules = $rules;
    }

    /**
     * Creates a collection from an array of rule data
     *
     * @param array $rulesData Array containing rule data
     *
     * @return self New collection instance
     */
    public static function createFromArray(array $rulesData): self
    {
        $rules = [];
        foreach ($rulesData as $ruleData) {
            $rules[] = RuleDTO::createFromArray($ruleData);
        }

        return new self(...$rules);
    }

    /**
     * @return \ArrayIterator<int,RuleDTO>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->rules);
    }

    /**
     * Returns the number of rules in the collection
     *
     * @return int Count of rules
     */
    public function count(): int
    {
        return count($this->rules);
    }

    /**
     * Checks if the collection is empty
     *
     * @return bool True if the collection contains no rules
     */
    public function isEmpty(): bool
    {
        return empty($this->rules);
    }

    /**
     * Returns the rules as an array
     *
     * @return array<RuleDTO> Array of rule DTOs
     */
    public function toArray(): array
    {
        return $this->rules;
    }
}
