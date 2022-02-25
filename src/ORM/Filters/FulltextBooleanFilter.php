<?php

namespace Fromholdio\FulltextFilters\ORM\Filters;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\FulltextFilter;

class FulltextBooleanFilter extends FulltextFilter
{
    private static $add_stars_to_value = false;

    protected function applyOne(DataQuery $query)
    {
        $value = $this->getValue();
        if (!empty($value))
        {
            // encode and quote
            $value = Convert::raw2sql($value, true);
            $this->model = $query->applyRelation($this->relation);
            $predicate = sprintf("MATCH (%s) AGAINST (? IN BOOLEAN MODE)", $this->getDbName());
            $query = $query->where([$predicate => $value]);
        }
        return $query;
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf("NOT MATCH (%s) AGAINST (? IN BOOLEAN MODE)", $this->getDbName());
        return $query->where([
            $predicate => Convert::raw2sql($this->getValue(), true)
        ]);
    }

    public function getValue()
    {
        $value = parent::getValue();
        return $this->convertValue($value);
    }

    protected function convertValue($value)
    {
        // make sure search string is not empty
        if (!is_string($value) || empty($value)) {
            return null;
        }
        $value = trim($value);

        // remove everything except normal words, numbers and spaces
        $value = preg_replace('/[^\p{L}\p{N} ]/u', ' ', $value);

        // remove multiple whitespace characters
        $value = preg_replace('/[\s]{2,}/', ' ', trim($value));

        // add * to all words (not numbers)
        $value = preg_replace('/([\p{L}]+)/u', '$1*', $value);

        // add + to all words (not numbers) to use AND
        $value = preg_replace('/([\p{L}*]+)/u', '+$1', $value);

        $doAddStars = static::config()->get('add_stars_to_value');
        return $doAddStars
            ? $this->addStarsToValue($value)
            : $value;
    }

    protected function addStarsToValue($value)
    {
        if (!trim($value)) {
            return "";
        }
        // Add * to each keyword
        $splitWords = preg_split("/ +/", trim($value));
        $newWords = [];
        for ($i = 0; $i < count($splitWords); $i++) {
            $word = $splitWords[$i];
            if ($word[0] == '"') {
                while (++$i < count($splitWords)) {
                    $subword = $splitWords[$i];
                    $word .= ' ' . $subword;
                    if (substr($subword, -1) == '"') {
                        break;
                    }
                }
            } else {
                $word .= '*';
            }
            $newWords[] = $word;
        }
        return implode(" ", $newWords);
    }
}
