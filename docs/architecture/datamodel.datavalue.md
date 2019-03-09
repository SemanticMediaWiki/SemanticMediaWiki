A [`DataValue`][datavalue] represents the user perspective on data which includes input and output specific rules.

The various subclasses of `DataValue` roughly correspond to the [datatypes][datatype] that a value can have, and they implement the specific behaviour of particular values. In the current architecture, `DataValue` subclasses implement all functions that are specific to a particular [datatype][datatype]:

- Interpreting user input (e.g. parsing a string into a calendar date)
- Encoding values in a format that is suitable for storage (e.g. computing a standardized, language-independent string for representing a date and a floating point number that can be used for sorting dates)
- Generating readable output (e.g. converting the internal representation of a date back into a text that is readable in the current language)

## Data representation and formatting

Each data value can have many forms of representation, for example, a text a user writes on a wiki page can have many forms that lead to the same value),  various display versions (e.g. augmented with links or tooltips), a unique internal representation (the value as the software sees it).

Some subclasses of `DataValue` have additional representations, (e.g. `TimeValue` to provide an output that is formatted according to ISO 8601. The `DataValue` class provides different get methods for obtaining different representation, and developers should read the software documentation of that class to understand when to use which method.

### Types and formatting

A general challenge is that datatypes are very diverse. Many values have an internal structure, e.g. dates have a year, month, and day component, whereas wiki page titles have a namespace identifier, title text, and interwiki component. This diversity makes it hard to treat values as single values of some primitive datatype. For example, in case of a wikipage, the internal representation is a list of values, obtained with different methods to represent the `DBKey`, `Namespace`, `Interwiki` data fragment  (e.g. representing wiki pages as strings would not be accurate, since it would be impossible to filter such values by namespace).

There is also a method `DataValue::getHash` that returns a string that can be used to compare two datavalues without looking at the details of their format.

### Output formats

Another challenge is the diversity of desirable output formats. Users typically want a large number of formatting options that are very specific to certain datatypes, so that it is hard to provide them via a unified interface. Moreover, output is used in MediaWiki both within HTML and within Wikitext contexts, requiring different formatting and treatment of special characters.

The output methods of `DataValue` reflect some of this diversity, and an additional facility of "output formats" (see `DataValue::setOutputFormat`) is provided for more fine-grained control to user when a specific output during a query request is sought. But obviously there must be limits of what can be achieved without cluttering the architecture, and users are advised to subclass their own datatype implementations for special formatting.

DataValue objects can be created directly via their own methods, but are required to be constucted using the [`DataValueFactory`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/DataValueFactory.php).

See also [`datatype`][datatype] for an explanation of the datatype system.

[dataitem]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.dataitem.md
[semanticdata]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.semanticdata.md
[datavalue]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datavalue.md
[datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md
