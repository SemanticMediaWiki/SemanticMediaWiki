The objective of the `SMW_NS_SCHEMA` (aka Schema) namespace is to allow for a structured definition of different schemata where types define the interpreter, syntax elements, and constraints.

The namespace expects a JSON format (or if available, YAML as superset of JSON) as input format to ensure that content elements are structured and a validation a [JSON schema][json:schema] can help enforce requirements and constraints for a specific type.

The following properties are provided to make elements of a schema definition discoverable.

* Schema type (`_SCHEMA_TYPE` )
* Schema definition (`_SCHEMA_DEF`)
* Schema description (`_SCHEMA_DESC`)
* Schema tag (`_SCHEMA_TAG`)
* Schema link (`_SCHEMA_LINK`)

## Registration

Extensibility for new schema types and interpreters is provided by adding a type to the `$smwgSchemaTypes` setting.

<pre>
$GLOBALS['smwgSchemaTypes'] = [
	'LINK_FORMAT_schema' => [
		'validator_schema => __DIR__ . '/data/schema/...',
		'group' => SMW_schema_GROUP_FORMAT,
	]
];
</pre>

[json:schema]: http://json-schema.org/
