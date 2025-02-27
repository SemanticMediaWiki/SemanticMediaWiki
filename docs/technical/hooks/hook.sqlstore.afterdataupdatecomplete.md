* Since: 2.3
* Description: Hook to process information after an update has been completed. Further provides `ChangeOp` to identify entities that have been added/removed during the update. (`SMWSQLStore3::updateDataAfter` was deprecated with 2.3)
* Reference class: [`SQLStoreUpdater.php`][SQLStoreUpdater.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\SQLStore\SQLStore;
use SMW\SemanticData;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::SQLStore::AfterDataUpdateComplete', function( SQLStore $store, SemanticData $semanticData, $changeOp ) {

	return true;
} );
```

## See also

[SQLStoreUpdater.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/SQLStoreUpdater.php
