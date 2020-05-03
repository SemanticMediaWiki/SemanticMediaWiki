Semantic MediaWiki provides a `SchemaFilter` to help developers define specific conditional aspects of a schema.

## Format

The following examples show the expected format for how to define a condition which is tested and hereby permits the code to decide whether it is a true statement or not for those tested values. Aside from the `if` + `keyword` notation, some statements allow to clarify the requirements when more than one value is given or required by using the following expressions `oneOf`, `anyOf`, and `allOf` (borrowing the semantics from  [swagger.io][swagger.io] and [json-schema.org][json-schema.org]).

```
{
	"if": {
		"category": { "oneOf" : [ "Foo", "Bar", "Foobar" ] }
	},
	"then": {
		"follow": "step_1"
	}
}
```

Building compsite filters is possible as well by combining different condition statements as the next example shows where the first condition expects a specific `namespace` while the `category` condition requires "allOf" its conditions to be met so that the entire composite block (which each individual filter) is to be true only when all requirements are fulfilled.

```
{
	"if": {
		"namespace": "NS_HELP",
		"category": { "allOf" : [ "Foo", "Bar", "Foobar" ] }
	},
	"then": {
		"follow": "step_1"
	}
}
```

## Filters

 The following filter conditionals are provided by default:

- The `category` conditional is implemented in `CategoryFilter`
- The `namespace` conditional is implemented in `NamespaceFilter`
- The `property` conditional is implemented in `PropertyFilter`

To be able to use above filters without modifications the following format is expected from a schema that relies on those conditionals. A single filter should be declared with something like:

```
"rule_name_x": {
	"if": {
		"conditional_x": "conditional_value_z"
	}
}
```

While expressing a composite filter should follow:

```
"rule_name_x": {
	"if": {
		"conditional_x_1": "conditional_value_z_1",
		"conditional_x_2": "conditional_value_z_2"
	}
}
```

It should be noted that it is possible to define a schema without a specific "rule_name_x" but it will create a disadvantage when trying to identify which rule was applied later in a production system when there are many possible rules to be filtered.

Some conditionals (category, namespace, property) can clarify their intention for testing a condition by using extra attributes such as `oneOf`, `anyOf`, `allOf`. For an understanding of the semantics, please consult the [swagger.io][swagger.io] and [json-schema.org][json-schema.org].

## Technical notes

The [`SchemaFilter`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/SchemaFilter.php) describes an interface for a specific filter implementation.

As with the example above, different filters can be combined to narrow the match pool of values to be tested against. To support composite filters any filter can be attached to another as a so called "node" filter to build something similar to a [decision tree][decision-tree] that is recursively traversed hereby tests on matches left from the previous filter.

```php
// Requirements to be tested against
$namespace = NS_MAIN;
$subject = '';

$schemaFactory = new SchemaFactory();
$schemaFilterFactory = $schemaFactory->newSchemaFilterFactory();

// Find an accumulated schema by type and which can included different pages
// from MediaWiki where the type is used
$schemaList = $schemaFactory->newSchemaFinder()->getSchemaListByType( 'SOME_SCHEMA' );

$callback = function() use( $subject ) {
	return $this->categoryLookup->findCategories( $subject );
};

// Could be a simple list of categories, or as in the demonstrated case a callback
// to lazy-load a possible expensive fetch
$categoryFilter = $schemaFilterFactory->newCategoryFilter(
	$callback
);

$namespaceFilter = $schemaFilterFactory->newNamespaceFilter(
	$namespace
);

// Similar to a decision tree, attach additional filters as "Node" filter
// so that matches can be further restricted on every succeeding filter
$compositeFilter = $schemaFilterFactory->newCompositeFilter(
	[
		$namespaceFilter,
		$categoryFilter
	]
);

$rules = $schemaList->newCompartmentIteratorByKey(
	'some_rules',

	// Allows to keep track of filter scores
	CompartmentIterator::RULE_COMPARTMENT
);

$compositeFilter->filter( $rules );

// Sort matches by filter score so that the "best" (aka. highest score) will be
// be first in the match set
$compositeFilter->sortMatches( CompositeFilter::SORT_FILTER_SCORE );

if ( $compositeFilter->hasMatches() ) {
	$matches = $compositeFilter->getMatches();
}
```

[swagger.io]: https://swagger.io/docs/specification/data-models/oneof-anyof-allof-not
[json-schema.org]: https://json-schema.org/understanding-json-schema/reference/combining.html
[decision-tree]: https://en.wikipedia.org/wiki/Decision_tree
