
A [DataItem][dataitem] represents the system perspective on the data to interact with a database in order to allow data to be managed, stored, and queried.

The `DataItem` class and its subclasses are the basic building block of all Semantic MediaWiki data elements. Its purpose is to provide a unified interface for all ''semantic entities'' that SMW deals with, e.g., numbers, dates, geo coordinates, wiki pages, and properties. It might be surprising that not only values but also subjects and properties are represented by a user facing [`DataValue`][datavalue] class. This makes sense since wiki pages can be both subjects and values, and since properties have many similarities with wiki pages (in particular the associated articles).

## Characteristics

Objects of class [DataItem][dataitem] represent a very simple piece of data. A [DataItem][dataitem] is similar to a primitive type in PHP (e.g. a PHP string or number): its identity is determined by its contents and nothing else. Dataitems should thus be thought of as "primitive values" that are merely a bit more elaborate than the primitive types in PHP. Their main characteristics are:

- [`Immutable`](https://en.wikipedia.org/wiki/Immutable_object) Once created, a dataitem cannot be changed.
- '''Context independent:''' The meaning of a dataitem is only based on its content, not on any contextual information (such as the information about the property it is assigned to).
- '''Limited shape:''' The kinds of datatitems (numbers, URLs, pages, ...) that SMW supports are limited and fixed. Extensions cannot add new kinds of dataitems, and programmers only need to handle a fixed list of possible kinds of datatitems.

Being immutable is essential for datatitems to behave like simple values. It imposes a restriction on programmers, but it also simplifies programming a lot since one does not have to be concerned about dataitems being changed by code that happens to have a reference to them.

## DataItem types

The available kinds of dataitems correspond to subclasses of [DataItem][dataitem]. For convenience, each kind of dataitem is also associated with a PHP constant called its "DIType". For example, instead of using a nested if-then-else statement with many <tt>instanceof</tt> checks, one can use a switch over this DIType to handle different cases. The following is a list all available dataitems:

- `DIWikiPage` (`DataItem::TYPE_WIKIPAGE`) Dataitems that represent a page in a wiki '''or''' a "subobject" of such a page. They are determined by the page title (string in MediaWiki DBkey format), namespace, interwiki code, and a subobject name (can be empty).
- `DIProperty` (`DataItem::TYPE_PROPERTY`) Dataitems that represent an SMW property. They are determined by the property key (which is the page DBKey string for user-defined properties), and the information whether or not they are inverted.
- `DINumber` (`DataItem::TYPE_NUMBER`) Dataitems that represent some number.
- `DIBlob` (`DataItem::TYPE_BLOB`) Dataitems that represent a string (of any length).
- `DIBoolean` (`DataItem::TYPE_BOOLEAN`) Dataitems that represent a truth value (true or false).
- `DIUri` (`DataItem::TYPE_URI`) Dataitems that represent a URI (or IRI) according to [RFC 3987](http://www.ietf.org/rfc/rfc3987.txt).
- `DITime` (DataItem::TYPE_TIME) Dataitems that represent a point in time in human or geological history. They are determined by a year, month, day, hour, minute, and (decimal) second, as well as a calendar model to interpret these values in (Julian or Gregorian).
- `DIGeoCoord` (`DataItem::TYPE_GEO`) Dataitems that represent a location on earth, represented by latitude and longitude.
- `DIContainer` (`DataItem::TYPE_CONTAINER`) Dataitems that represent a set of SMW facts, represented by an object of type [SemanticData][semanticdata].
- `DIConcept` (`DataItem::TYPE_CONCEPT`) Dataitems that represent the input and feature information for some SMW [concept](https://www.semantic-mediawiki.org/wiki/Help:Concepts)(query, description, features in query, size and depth)
- `DIError` (`DataItem::TYPE_ERROR`) Dataitems that represent a list of errors (array of string). Used to gently pass on errors when dataitem return types are expected.
- (`DataItem::TYPE_NOTYPE`) Additional DIType constant that is used to indicate that the type is not known at all.

### Type restriction

The restriction to these types of dataitem may at first look like a major limitation, since it means that SMW can only represent limited forms of data. For example, there is no dataitem for storing the structure of chemical formulae &ndash; doesn't this mean that SMW can never handle such data? No, because the existing datatitems can be used to keep all required information (for example by representing chemical formulae as strings). The task of interpreting this basic data as a chemical formula has to be handled on higher levels that deal with user input and output using a [DataValue][datavalue].

### Container type

There is one kind of dataitem, the `DIContainer`, that represents "values" that consist of many facts (subject-property-value triples); almost all complex forms of data that SMW does not have a dataitem for could be accurately represented in this format. This type uses the [SemanticData][semanticdata] as object representation.

## Technical notes

Creating dataitems is very easy: just call the constructor of the dataitem with the required values. Note that dataitems are strict about data quality: they are not meant to show the error-tolerance of the SMW user interface. For a programmer, it is more useful to see a clear error than to have SMW use some "repaired" or partly "guessed" value when a problem occurred. When trying to create dataitems from illegal data (e.g. trying to make a wikipage for an empty page title), an exception will be thrown. Usually dataitems will only implement basic data validation to avoid complex computations. If strict validation of, say, a URI string is needed, then own methods need to be implemented.

The [DataItem][dataitem]  implements a standard interface that allows useful operations like serialization and deserialization (a second way to create them from serialized strings). They also can generate a string hash code to efficiently compare their contents. Each dataitem also implements basic get methods to access the data, and sometimes other helper methods that are useful for the given kind of data.

The important thing is to keep data items reasonably lean and simple data containers &ndash; complex parsing or formatting functions are implemented elsewhere.

[dataitem]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.dataitem.md
[semanticdata]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.semanticdata.md
[datavalue]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datavalue.md
