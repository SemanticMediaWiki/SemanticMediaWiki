# SQLStore

The `SQLStore` consists of a storage and query engine to manage semantic data structures with the help of a `SQL` back-end.

## QueryEngine

The `QueryEngine` handles the transformation of the `ask` query language into a `SQL` construct and is also 
responsible to return query results from the `SQL` back-end with the help of the following components:

- The `QueryBuilder` transforms `ask` descriptions into individual `QuerySegment`'s (aka `QuerySegmentList`)
- The `DescriptionInterpreter` interface describes classes that are responsible to interpret a specific
  `Description` object and turn it into an abstract `SQL` construct (a `QuerySegment`)
- The `QuerySegmentListResolver` flattens and transforms a list of `QuerySegment`'s into a non-recursive
  tree of `SQL` statements (including resolving of property/category hierarchies)
- The `ConceptQueryResolver` encapsulates query processing of a concept description in connection
  with the `ConceptCache` class

```php
// Equivalent to condition [[Foo::+]]
$description = new SomeProperty(
	new DIProperty( 'Foo' ),
	new ThingDescription()
);

$query = new Query( $description );
$query->setLimit( 10 );

$sqlStorefactory = new SQLStoreFactory(
  new SQLStore()
);

$queryEngine = $sqlStorefactory->newMasterQueryEngine();
$queryResult = $queryEngine->getQueryResults( $query );
```
