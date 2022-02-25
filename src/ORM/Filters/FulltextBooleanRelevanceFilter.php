<?php

namespace Fromholdio\FulltextFilters\ORM\Filters;

use SilverStripe\ORM\DataQuery;

class FulltextBooleanRelevanceFilter extends FulltextBooleanFilter
{
    protected $weight;

    public function __construct(?string $fullName = null, $value = false, array $modifiers = [], int $weight = 1)
    {
        parent::__construct($fullName, $value, $modifiers);
        $this->setWeight($weight);
    }

    protected function applyOne(DataQuery $query)
    {
        $value = $this->getValue();
        if (!empty($value))
        {
            $query = parent::applyOne($query);
            // add select statement
            $alias = $this->getRelevanceAlias();
            $score = $this->getScoreName();
            $select = sprintf("{$score} := MATCH (%s) AGAINST ({$value} IN BOOLEAN MODE)", $this->getDbName());
            $weight = $this->getWeight();
            $select .= ' * ' . $weight;
            $query->selectField($select, $alias);
        }
        return $query;
    }

    public function setWeight(int $weight)
    {
        $this->weight = $weight;
        return $this;
    }

    public function getWeight()
    {
        return (int) $this->weight;
    }

    public function getRelevanceAlias()
    {
        return $this->getName() . 'Relevance';
    }

    public function getScoreName()
    {
        return '@' . $this->getName() . 'Score';
    }
}
