* Since: 3.1
* Description: Hook to extend the `SemanticData` object before the update is completed. (`SMWSQLStore3::updateDataBefore` was deprecated with 3.1)
* Reference class: `SMWSQLStore3Writers`

### Signature

```php
use Hooks;
use SMW\SQLStore\SQLStore;
use SMW\SemanticData;

Hooks::register( 'SMW::SQLStore::BeforeDataUpdateComplete', function( SQLStore $store, SemanticData $semanticData ) {

	return true;
} );
```
