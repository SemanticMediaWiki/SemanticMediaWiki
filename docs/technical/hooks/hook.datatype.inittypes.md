* Since: 1.9
* Description: Hook to add additional [DataType][datamodel.datatype] support (`smwInitDatatypes` was deprecated with 1.9)
* Reference class: [`DataTypeRegistry.php`][DataTypeRegistry.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\DataTypeRegistry;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::DataType::initTypes', function( DataTypeRegistry $dataTypeRegistry ) {

	return true;
} );
```

## See also

- [datamodel.datatype.md][datamodel.datatype]
- [register.custom.datatype.md][custom.datatype]

[DataTypeRegistry.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/DataTypeRegistry.php
[custom.datatype]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.datatype.md
[datamodel.datatype]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.datatype.md