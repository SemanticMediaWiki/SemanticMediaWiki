* Since: 2.1
* Description: Hook to manipulate a `QueryResult` after the selection process
* Reference class: `SMW_SQLStore3.php`

### Signature

```php
use MediaWiki\MediaWikiServices;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Store::AfterQueryResultLookupComplete', function( $store, &$queryResult ) {

	return true;
} );
```

## See also

- [`hook.store.afterqueryresultlookupcomplete.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.store.afterqueryresultlookupcomplete.md)
