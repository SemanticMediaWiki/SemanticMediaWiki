## Objective

The objective of the `SMW_NS_SCHEMA` (aka [`smw/schema`][ns:schema]) namespace provides a structured definition space where different schemata  types can be defined and that independently use a type specific interpreter, syntax elements, and possible constraints.

The namespace expects a JSON format (or if available, YAML as superset of JSON) as input format to ensure that content elements are structured and a `validation_schema` (see [JSON schema][json:schema]) can be assigned to help and enforce requirements and constraints for a specific type.

The following annotation properties are provided to make elements of a schema and its definition discoverable.

* Schema type (`_SCHEMA_TYPE` )
* Schema definition (`_SCHEMA_DEF`)
* Schema description (`_SCHEMA_DESC`)
* Schema tag (`_SCHEMA_TAG`)
* Schema link (`_SCHEMA_LINK`)

## Registration

Extensibility for new schema types and interpreters is provided by adding a new type to the `SchemaTypes::defaultTypes` setting or via the [`SMW::Schema::RegisterSchemaTypes`][SMW::Schema::RegisterSchemaTypes] hook.

<pre>
$schemaTypes = [
	'FOO_SCHEMA' => [
		'group' => SMW_SCHEMA_FOO_GROUP,
		'validation_schema => __DIR__ . '/data/schema/foo-schema.v1.json',
	]
];
</pre>

In the example above, `FOO_SCHEMA` refers to the type name and any attributes assigned to that type will be used when constructing a schema instance. Types can define individual attributes that may be use exclusively by the type.
 - `group` defines types belonging to the same schemata class
 - `validation_schema` links to the [JSON schema][json:schema] expected to be used

## Available types

- [`LINK_FORMAT_SCHEMA`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/link.format.md)
- [`SEARCH_FORM_SCHEMA`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/search.form.md)
- [`PROPERTY_GROUP_SCHEMA`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/property.group.md)
- [`PROPERTY_CONSTRAINT_SCHEMA`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/property.constraint.md)
- [`CLASS_CONSTRAINT_SCHEMA`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/class.constraint.md)
- [`PROPERTY_PROFILE_SCHEMA`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/property.profile.md)

## Technical notes

<pre>
/src/Schema (SMW\Schema)
│	├─ Compartment
│	├─ CompartmentIterator
│	├─ Schema
│	├─ SchemaDefinition
│	├─ SchemaFactory
│	├─ SchemaValidator
│	├─ SchemaFilterFactory
│	└─ SchemaFilter
│		│
│		/src/Schema/Filters (SMW\Schema\Filters)
│		├─ CategoryFilter
│		├─ NamespaceFilter
│		├─ PropertyFilter
│
/src/MediaWiki (SMW\MediaWiki)
	└─ Content
		├─ SchemaContent
		├─ SchemaContentFormatter
		└─ SchemaContentHandler
</pre>

### Filters

[Filter](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/filter.md) conditions can be defined as aprt of a type to provide means to create conditional requirements.

### Validation

As outlined above, the use of a [JSON schema][json:schema] is an important part of a provided type to ensure that only specific data in an appropriate format can be stored and is validated against a normed vocabulary.

[ns:schema]: https://www.semantic-mediawiki.org/wiki/Help:Schema
[json:schema]: http://json-schema.org/
[SMW::Schema::RegisterSchemaTypes]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.schema.registerschematypes.md
