To represent semantic information, a collection of [`DataItem`][dataitem] objects need to be combined into facts. For this purpose, the class [`SemanticData`][semanticdata] provides a basic construct for handling sets of facts that refer to the same subject. This makes sense since it is by far the most common case that the subject is the same for many facts (e.g. all facts on one page, or all facts in one row of a query result).

A `SemanticData` object further groups values by property: it has a list of properties, and for each property a list of values. Again this reflects common access patterns and avoids duplication of information. The data contained in `SemanticData` can still be viewed as a set of subject-property-value triples, but Semantic MediaWiki has no dedicated way to represent such triples, i.e. there is no special class for representing one fact.

Semantic MediaWiki generally uses the `SemanticData` object whenever sets of triples are collected and referenced. If many subjects are involved, then one may use an array of `SemanticData` objects. In other cases, one only wants to consider a list of `DataValue` instead of whole facts, e.g. when fetching the list of all pages that are annotated with a given property-value pair (e.g. all things located in France). In this case, the facts are implicit (one could combine the query parameters "located_in" and "France" with each of the result values).

## ContainerSemanticData

`ContainerSemanticData` is an object that collects `SemanticData` as a container. Containers are not dataitems in the proper sense: they do not represent a single, opaque value that can be assigned to a property. Rather, a container represents a "subobject" with a number of property-value assignments.

When a container is stored, these individual data assignments are stored -- the data managed by SMW never contains any "container", just individual property assignments for the subobject. Likewise, when a container is used in search, it is interpreted as a patterns of possible property assignments, and this pattern is searched for.

The data encapsulated in a container data item is essentially an `SemanticData`object of class `ContainerSemanticData`. This class allows the subject to be kept anonymous if not known (if no context page is available for finding a suitable subobject name)

Being a mere placeholder/template for other data, an `DIContainer` is not immutable as the other basic data items. New property-value pairs can always be added to the internal `ContainerSemanticData`.

## QueryResult

Another important case are query results. They have their own special container class `QueryResult` which is similar to a list of `SemanticData`objects for each row, but has some more intelligence to fetch the required data only on demand.

[dataitem]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.dataitem.md
[semanticdata]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.semanticdata.md