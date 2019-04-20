* Since: 3.0
* Description: Hook to process information about an entity where the clean-up has been finalized.
* Reference class: [`PropertyTableIdReferenceDisposer.php`][PropertyTableIdReferenceDisposer.php]

### Signature

```php
use Hooks;
use SMW\Store;
use SMW\DIWikiPage;

Hooks::register( 'SMW::SQLStore::EntityReferenceCleanUpComplete', function( Store $store, $id, DIWikiPage $subject, $isRedirect ) {

	return true;
} );
```

## See also

- For a usage example, see the [`SemanticCite`](https://github.com/SemanticMediaWiki/SemanticCite) or [`SemanticExtraSpecialProperties`](https://github.com/SemanticMediaWiki/SemanticExtraSpecialProperties) extension

[PropertyTableIdReferenceDisposer.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/PropertyTableIdReferenceDisposer.php