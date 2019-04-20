* Since: 2.1
* Description: Hook to return a `QueryResult` object before the standard selection process is started and allows to suppress the standard selection process completely by returning `false`.
* Reference class: `SMW_SQLStore3.php`

### Signature

```php
use Hooks;

Hooks::register( 'SMW::Store::AfterQueryResultLookupComplete', function( $store, $query, &$queryResult, $queryEngine ) {

	// Allow default processing
	return true;

	// Stop further processing
	return false;
} );
```