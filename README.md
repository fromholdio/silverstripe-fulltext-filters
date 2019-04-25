# silverstripe-fulltext-filters

This module adds three fulltext `SearchFilter`s to your SilverStripe project.

* `FulltextBoolean` - similar to the existing `Fulltext` filter, but uses `IN BOOLEAN MODE`, and transforms the search phrase to take full advantage of this. [See characteristics of Boolean full-text searches here.](https://dev.mysql.com/doc/refman/8.0/en/fulltext-boolean.html)
* `FulltextRelevance` - adds relevance to the select query, accepts a 'Weight' argument which is applied to the relevance score, and provides hooks to sort results by the sum of all weighted indexes within the query
* `FulltextBooleanRelevance` - same as `FulltextRelevance` but uses the `FulltextBoolean` filter as its base and basis for matching. Uses the Boolean mode relevance calculation (which can differ from Fulltext in Natural Language Mode)

N.b. - Full-text relevance scores are binary (1 or 0) in Boolean Mode, unless the table engine is InnoDB. By default SilverStripe enforces MyISAM for tables containing a fulltext index. Use [fromholdio-silverstripe-fulltext-innodb](https://github.com/fromholdio/silverstripe-fulltext-innodb) to overcome this.

## Requirements

* [silverstripe-framework](https://github.com/silverstripe/silverstripe-framework) ^4

## Recommended

* [fromholdio/silverstripe-fulltext-innodb](https://github.com/fromholdio/silverstripe-fulltext-innodb) ^1.0

## Installation

`composer require fromholdio/silverstripe-fulltext-filters`

## Details & Usage

In short, use this like any other set of `SearchFilters`:

```php
// Define your fulltext indexes:
private static $indexes = [
    'TitleFields' => [
        'type' => DBIndexable::TYPE_FULLTEXT,
        'columns' => [
            'Title',
            'MenuTitle'
        ]
    ],
    'ContentFields' => [
        'type' => DBIndexable::TYPE_FULLTEXT,
        'columns' => [
            'Content',
            'MetaDescription'
        ]
    ]
];

// Use in ORM filters:

// We're using BOOLEAN MODE matching
// Matches to the TitleFields index are weighted as five times more significant than ContentFields matches
// We're calculating the sum of relevance scores per matched record, and ordering the results by descending relevance score

public function getResults($value)
{
    $objects = ObjectClass::get()
        ->filterAny([
            'TitleFields:FulltextBooleanRelevance(5)' => $value,
            'ContentFields:FulltextBooleanRelevance' => $value
        ])
        ->sort('@TitleFieldsScore + @ContentFieldsScore DESC');
}
```


More documentation to come. Look at the source code in the meantime, or submit an issue.

Two key hooks:

* You can access the relevance value the database assigns per index and per record using `->{IndexName}Relevance`
* Per the example above, these relevance values are made available within the query for `sort` but also available for additional `where`/etc, using variable names `@{IndexName}Score`
 

## To Do

* Documentation 😅
