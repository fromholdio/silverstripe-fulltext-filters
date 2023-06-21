<?php

namespace Fromholdio\FulltextFilters\ORM\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;

class FulltextBooleanRelevanceFilter extends FulltextBooleanFilter
{
    protected $weight;

    public function __construct(?string $fullName = null, $value = false, array $modifiers = [], int $weight = 1)
    {
        parent::__construct($fullName, $value, $modifiers);
        $this->setWeight($weight);
    }

    /**
     * fix string quoting for value
     */
    protected function applyOne(DataQuery $query)
    {
        $value = $this->getValue();
        if ($value && strlen($value) > 0) {
            // encode and quote
            $value = Convert::raw2sql($value, true);
            // copied from parent class, but use encoded value for where statement
            $this->model = $query->applyRelation($this->relation);
            $predicate = sprintf("MATCH (%s) AGAINST (? IN BOOLEAN MODE)", $this->getDbName());
            $query->where([$predicate => $value]);
            // add select statement
            $alias = $this->getRelevanceAlias();
            $score = $this->getScoreName();
            // escape % with %% for sprintf()
            $value = str_replace('%', '%%', $value);
            $select = sprintf("{$score} := MATCH (%s) AGAINST ({$value} IN BOOLEAN MODE)", $this->getDbName());
            $weight = $this->getWeight();
            $select = $select . ' * ' . $weight;
            $query->selectField($select, $alias);
        }
        return $query;
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf("NOT MATCH (%s) AGAINST (? IN BOOLEAN MODE)", $this->getDbName());
        return $query->where([$predicate => Convert::raw2sql($this->getValue(), true)]);
    }

    /**
     * Override to skip regex processors to fix issues with special characters.
     * Add + to all words to make search always use AND.
     *
     * {@inheritDoc}
     * @see \Fromholdio\FulltextFilters\ORM\Filters\FulltextBooleanFilter::convertValue()
     */
    protected function convertValue($value)
    {
        // make sure search string is not empty
        if (!is_string($value) || !strlen(trim($value))) {
            return null;
        }
        $value = trim($value);

        // remove everything except normal words, spaces and %
        $value = preg_replace('/[^a-zA-Z0-9 %]/', ' ', $value);

        // also remove % if not after number
        $value = preg_replace('/([^0-9])%/', '$1', $value);

        // remove multiple whitespace characters
        $value = preg_replace('/[\s]{2,}/', ' ', trim($value));

        // add + to all words to use AND for all words and numbers
//        $value = preg_replace('/([a-zA-Z0-9]+)/', '+$1', $value);

        return $value;
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
