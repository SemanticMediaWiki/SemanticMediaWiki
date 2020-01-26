Semantic MediaWiki provides a `SchemaFilter` to help developers define specific conditional aspects of a schema.

## Format

The following examples show the expected format for how to define a condition which is tested and hereby permits the code to decide whether it is a true statement or not for those tested values. Aside from the `if` + `keyword` notation, some statements allow the clarify the requirements when more than one value is given or required by using the following expressions `oneOf`, `anyOf`, and `allOf` (borrowing the semantics from  [swagger.io][swagger.io] and [json-schema.org][json-schema.org]).

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

It should be noted that it is possible to define a schema without a specific "rule_name_x" but it will create a disadvantage when trying to identify which rule was applied later in a production system when there are dozen of possible rules to be filtered.

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

// Find a schema/list by type and which can included different pages
// from MediaWiki where the type is used
$schemaList = $schemaFactory->newSchemaFinder()->getSchemaListByType( 'SOME_SCHEMA' );

$callback = function() use( $subject ) {
	return $this->categoryLookup->findCategories(
		$subject
	);
};

$categoryFilter = $schemaFilterFactory->newCategoryFilter(
	// Expensive fetch, lazy-load when required due to DB lookup
	$callback
);

$namespaceFilter = $schemaFilterFactory->newNamespaceFilter(
	$namespace
);

// As with a decision tree attach an additional filter as "Node" filter
// so that matches can be further restricted by the node filter
$namespaceFilter->setNodeFilter(
	$categoryFilter
);

$namespaceFilter->filter(
	$schemaList->newCompartmentIteratorByKey( 'some_rules' )
);

if ( $namespaceFilter->hasMatches() ) {
	// Act on those compartments that matched the condition(s)
	$matches = $namespaceFilter->getMatches();
}
```

[swagger.io]: https://swagger.io/docs/specification/data-models/oneof-anyof-allof-not
[json-schema.org]: https://json-schema.org/understanding-json-schema/reference/combining.html
[decision-tree]: https://en.wikipedia.org/wiki/Decision_tree