The datamodel contains the most essential architectural choice of Semantic MediaWiki for the management of its data. It specifies the way in which semantic information are represented within the system (actually it specifies what a semantic information fragment is).

## Fact statement

Data in Semantic MediaWiki are represented by property-value pairs that are assigned to objects. For example, the statement ''Dresden (object) has a population (property) of 517,052 (value)'' involves:

- Dresden as `object`
- population as `property` and
- 517,052 as `value`

### Objects, properties, and values

To elaborate on the schema we can further clarify that:

- The described ''objects'' are normally wiki pages.
- ''Properties'' can be used everywhere. They do not belong to specific wiki pages, categories, etc.
- ''Values'' can be of different types (numbers, dates, other wiki pages, ...).
- The [`datatype`][datatype] is part of the value's identity (values of different types are different, even if they are based on the same user input).
- Objects can have zero, one, or many values for any property.
- A semantic fact is completely specified by its object, property, and value. For instance, it does not matter who specified a fact (in contrast to tagging systems where each user can have individual tags for the same thing).
- Facts are either given or not given, but they cannot be given more than once (again in contrast to tagging systems where we count how often a tag has been given to a resource).
- "Object" is a very general term, so we often use "subject" when we want to emphasize that a thing is the subject of a property-value assignment in a fact.

These ideas are reflected in a basic data model where:

- All elements of a fact are represented by PHP objects of (subclasses of) the class [`DataItem`][dataitem]
- Sets of semantic facts in turn are represented by objects of type [`SemanticData`][semanticdata].

## Data representation

To represent facts and fact statements, Semantic MediaWiki uses two different perspectives to help organize the data and make them available depending on the consumer that requests the data.

### System perspective

The "system perspective" describes on how to directly manipulate data on a database level in order for them to be managed, stored, and queried. This section describes the basic software components that are involved in representing those data.

- [`DataItem`][dataitem] represents the system perspective on the data to interact with a database
- [`SemanticData`][semanticdata] represent semantic information as a collection of [`DataItem`][dataitem] objects

### User perspective

The "user perspective" incorporates the basic data model and its technical realization through the use of [`DataItem`][dataitem] and [`SemanticData`][semanticdata] containers and adds a representation layer (which is the user facing input/output) in form of datavalues.

- [`DataValue`][datavalue] represents the user perspective on data which includes input and output specific rules.
- [`Datatype`][datatype] values are organized in datatypes that define how user inputs are interpreted and how data is presented in outputs.

[dataitem]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.dataitem.md
[semanticdata]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.semanticdata.md
[datavalue]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datavalue.md
[datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md
