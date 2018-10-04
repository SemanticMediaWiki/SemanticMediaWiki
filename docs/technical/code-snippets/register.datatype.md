## Register new datatype

This example shows how to register a new dataType/dataValue in Semantic MediaWiki.

* [Datatype](https://www.semantic-mediawiki.org/wiki/Help:Datatype)
* [DataValue](https://www.semantic-mediawiki.org/wiki/Help:DataValue)

### Type registration

All IDs must start with an underscore, two underscores indicate a truly internal
(non user-interacted type), three underscores should be used by an extension.

`TypesRegistry::getDataTypeList` expects that the following information are provided:

* A type id (e.g. `FooValue::TYPE_ID`)
* An associated class
* An item type (storage type)
* A declaration whether it is a subData type (e.g subobject) or not
* Whether a type is browsable or not

<pre>
return array(
	// ...
	FooValue::TYPE_ID => [ FooValue::class, DataItem::TYPE_WIKIPAGE, false, false ],
);
</pre>

<pre>
class FooValue extends DataValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '_foo';

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $userValue ) {
		...
	}

}
</pre>

### Label registration

By default, DataTypes (Date, URL etc.) are registered with a corresponding property
of the same name to match the expected semantics. For an exemption, see
`smwgDataTypePropertyExemptionList`.

Find `i18n/extra/en.json` and extend the canonical description (which is English) with something like:

<pre>
	"dataTypeLabels":{
		"_foo": "SomeType"
		...
	},
	"dataTypeAliases":{
		"SomeType": "_foo"
		"ExtraAlias": "_foo"
		...
	},
</pre>
