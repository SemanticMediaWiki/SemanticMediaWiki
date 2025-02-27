* Since: 3.1
* Description: Hook to override SMWs implementation of default result format handling. Replaces the `SMWResultFormat` hook.
* Reference class: [`ResultFormat.php`][ResultFormat.php]

### Signature

```php
use MediaWiki\MediaWikiServices;

\MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::ResultFormat::OverrideDefaultFormat', function( &$format, $printRequests ) {

	return true;
} );
```

[ResultFormat.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/ResultFormat.php
