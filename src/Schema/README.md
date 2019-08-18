## Objective

The objective of the `SMW_NS_SCHEMA` (aka [`smw/schema`][ns:schema]) namespace is to allow for a structured definition of different schemata where types define the interpreter, syntax elements, and constraints.

The namespace expects a JSON format (or if available, YAML as superset of JSON) as input format to ensure that content elements are structured and a `validation_schema` (see [JSON schema][json:schema]) may be assigned to a type to help enforce requirements and constraints for a specific type.

The following annotation properties are provided to make elements of a schema and its definition discoverable.

* Schema type (`_SCHEMA_TYPE` )
* Schema definition (`_SCHEMA_DEF`)
* Schema description (`_SCHEMA_DESC`)
* Schema tag (`_SCHEMA_TAG`)
* Schema link (`_SCHEMA_LINK`)

## Registration

Extensibility for new schema types and interpreters is provided by adding a new type to the `$smwgSchemaTypes` setting.

<pre>
$GLOBALS['smwgSchemaTypes'] = [
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

[json:schema]: http://json-schema.org/

## Technical notes

<pre>
SMW\Schema
│	│
│	├─ Schema
│	├─ SchemaDefinition
│	├─ SchemaFactory
│	└─ SchemaValidator
│
SMW\MediaWiki
	└─ Content
		├─ SchemaContent
		├─ SchemaContentFormatter
		└─ SchemaContentHandler
</pre>

[ns:schema]: https://www.semantic-mediawiki.org/wiki/Help:Schema
