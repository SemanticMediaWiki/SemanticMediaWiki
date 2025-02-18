* Since: 3.1
* Description: Hook to extend the `SemanticData` object before the update is completed. (`SMWSQLStore3::updateDataBefore` was deprecated with 3.1)
* Reference class: [`SQLStoreUpdater.php`][SQLStoreUpdater.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\SQLStore\SQLStore;
use SMW\SemanticData;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::SQLStore::BeforeDataUpdateComplete', function( SQLStore $store, SemanticData $semanticData ) {

	return true;
} );
```

[SQLStoreUpdater.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/SQLStoreUpdater.php