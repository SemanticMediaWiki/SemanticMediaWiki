* Since: 2.3
* Description: Hook to add update jobs while running the rebuild process. (`smwRefreshDataJobs` was deprecated with 2.3)
* Reference class: [`Rebuilder.php`][Rebuilder.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\SQLStore\SQLStore;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::SQLStore::BeforeDataRebuildJobInsert', function( SQLStore $store, array &$jobs ) {

	return true;
} );
```

[Rebuilder.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/Rebuilder/Rebuilder.php
