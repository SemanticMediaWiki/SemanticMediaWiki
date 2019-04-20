* Since: 3.1
* Description: Hook to process information after an update has been completed. (`SMWStore::updateDataAfter` was deprecated with 3.1)
* Reference class: [`Store.php`][Store.php]

### Signature

```php
use Hooks;
use SMW\Store;
use SMW\SemanticData;

Hooks::register( 'SMW::Store::AfterDataUpdateComplete', function( Store $store, SemanticData $semanticData ) {

	return true;
} );
```

[Store.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Store.php