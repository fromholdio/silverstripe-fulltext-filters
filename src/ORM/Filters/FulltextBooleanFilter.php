<?php

namespace Fromholdio\FulltextFilters\ORM\Filters;

use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\Filters\FulltextFilter;

class FulltextBooleanFilter extends FulltextFilter
{
    protected function applyOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf("MATCH (%s) AGAINST (? IN BOOLEAN MODE)", $this->getDbName());
        return $query->where(array($predicate => $this->getValue()));
    }

    protected function excludeOne(DataQuery $query)
    {
        $this->model = $query->applyRelation($this->relation);
        $predicate = sprintf("NOT MATCH (%s) AGAINST (? IN BOOLEAN MODE)", $this->getDbName());
        return $query->where(array($predicate => $this->getValue()));
    }

    public function getValue()
    {
        $value = parent::getValue();
        return $this->convertValue($value);
    }

    protected function convertValue($value)
    {
        $andProcessor = function ($matches) {
            return ' +' . $matches[2] . ' +' . $matches[4] . ' ';
        };
        $notProcessor = function ($matches) {
            return ' -' . $matches[3];
        };

        $value = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $value);
        $value = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $value);
        $value = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $value);
        $value = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $value);

        return $this->addStarsToValue($value);
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
