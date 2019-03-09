The following example demonstrates how to register a new [`DataType`][datatype] and [`DataValue`][datavalue] in Semantic MediaWiki.

### Type registration

All IDs must start with an underscore, two underscores indicate a truly internal (non user-interacted type), three underscores should be used by an extension.

`TypesRegistry::getDataTypeList` expects that the following information are provided:

* A type id (e.g. `FooValue::TYPE_ID`)
* An associated class
* An item type (storage type)
* Boolean on whether it is a subData type (e.g subobject) or not
* Boolean on whether the type is browsable or not

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

By default, DataTypes (Date, URL etc.) are registered with a corresponding property of the same name to match the expected semantics. For an exemption, see `smwgDataTypePropertyExemptionList`.

Find `i18n/extra/en.json` and extend the canonical description (which is English) with something like:

<pre>
	"datatype":{
		"labels": {
			"_foo": "SomeType"
			...
		}
		"aliases": {
			"SomeType": "_foo"
			"ExtraAlias": "_foo"
			...
		}
	},
</pre>

[datavalue]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datavalue.md
[datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md