Users can pick many datatypes for their data. Yet they do not specify the type for each value they write, but assign one global type to a property. This is slightly different from SMW's internal architecture, where dataypes influence a value's identity, whereas all properties are represented by values of a single type `PropertyValue`. This is not a problem, it simply says that the type information that users provide for each property is interpreted as "management information" that SMW uses to interpret user inputs. The [data model][datamodel] is still as described above, with types being part of the values (which is where they are mainly needed).

Again, the typing approach in the user interface does not affect the data model but helps SMW to make sense of user input. One could equivalently dispense with the property-related types and require users to write the type for each input value instead. This would simply be cumbersome and would prevent some implementation optimizations that are now possible since we can assume that properties have values of only one type that we know.

## Technical notes

### Defining a type

Users refer to types by using natural language labels such as datatype `[[Has type::Date]]`. These labels are subject to internationalization. There can also be aliases for some types to allow multiple spellings in one language. To make SMW code independent from the selected language, SMW uses internal [`TYPE_ID`][typesregistry] for referring to datatypes. These are short strings that typically start with `_` to avoid confusion with page titles.

For example, `_dat` is the type ID for the `Date` type. Developers should always use the internal type IDs. The correspondence of user labels and internal IDs is defined in language files found in [`i18n/extra`][i18n-extra] folder.

See the [`register.core.datatype.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.core.datatype.md) for "How to register a core data type".

#### Implementing type-specific behavior

How do type IDs relate to the subclasses of `DataValue` that implement type-specific behavior?

The answer is that one such class may take care of one or more type IDs. For example, the handling of URLs and Email addresses has many things in common, so there is just one class `URIValue` that handles both. The datavalue object is told its type ID on creation, so it can adjust its behavior to suit more than one type.

The association of internal type IDs with the classes that should be used to represent their objects is done in the [`TypesRegistry`][typesregistry] together with the [`DataTypeRegistry`][datatypetegistry] that includes some hooks and can be used to extend and change these associations. This allows developers to add new types and even register their own implementations for existing types without patching code.

The [`DataValueFactory`][datavaluefactory] class should be used to create an instance representation for a data value object in SMW, since otherwise the associations (that someone might have overwritten) would not be honored.

### Register a custom type and data value

Some datavalue classes provide special methods, e.g. for getting the year component of a date, and parts of SMW (extension) code that use such methods must first check if they are dealing with the right class (you cannot rely on the type ID to determine the class). This also means that developers who overwrite SMW implementations may want to subclass the existing classes to ensure that checks like <tt>( $datavalue instanceof `TimeValue`)</tt> succeed (if not, a modified time class might not work with some time-specific features).

Own datatypes should always use type IDs that start with "___" (three underscores) to avoid (future) name clashes with SMW types.

Finally, there are some datatypes that are internal to SMW. They use IDs starting with two underscores, and are not assigned to any user label. They cannot be accessed in a wiki and are only available to developers.

The purpose of these types is usually to achieve a special handling when storing data. For example, values of `Subproperty of` could be represented by a `_wpg` datatype (aka page type) but it uses a special internal type that ensures that the data can be stored separately and in a form that simplifies its use in query answering.

A datatype that is added by an extension becomes internal if it is not given any user label. In this case, users cannot create properties with this type, but everything else works normally (in particular, SMW will not know anything special about internal extension types and will just treat them like any other extension type).

See the [`register.custom.datatype.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.datatype.md) for "How to register a custom data type".

## Examples

- [CitationReferenceValue.php](https://github.com/SemanticMediaWiki/SemanticCite/blob/master/src/DataValues/CitationReferenceValue.php) (Semantic Cite)
- [CoordinateValue.php](https://github.com/JeroenDeDauw/Maps/blob/master/src/SemanticMW/DataValues/CoordinateValue.php) (Maps)
- [NotificationGroupValue.php](https://github.com/SemanticMediaWiki/SemanticNotifications/blob/master/src/DataValues/NotificationGroupValue.php) (Semantic Notifications)

[datamodel]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.md
[dataitem]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.dataitem.md
[semanticdata]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.semanticdata.md
[datavalue]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datavalue.md
[datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md
[typesregistry]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/TypesRegistry.php
[datatypetegistry]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/DataTypeRegistry.php
[datavaluefactory]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/DataValueFactory.php
[i18n-extra]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/master/i18n/extra