* Since: 2.3
* Description: Hook to process information after an update has been completed. Further provides `ChangeOp` to identify entities that have been added/removed during the update. (`SMWSQLStore3::updateDataAfter` was deprecated with 2.3)
* Reference class: [`PropertyTableDefinitionBuilder.php`][PropertyTableDefinitionBuilder.php]

### Signature

```php
use Hooks;
use SMW\SQLStore\SQLStore;
use SMW\SemanticData;

Hooks::register( 'SMW::SQLStore::AfterDataUpdateComplete', function( SQLStore $store, SemanticData $semanticData, $changeOp ) {

	return true;
} );
```

## See also


[PropertyTableDefinitionBuilder.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/PropertyTableDefinitionBuilder.php