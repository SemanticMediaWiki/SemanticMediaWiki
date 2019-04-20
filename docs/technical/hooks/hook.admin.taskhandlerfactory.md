* Since: 3.0
* Description: Hook allows to extend available `TaskHandler` in `Special:SemanticMediaWiki`
* Reference class: [`TaskHandlerFactory.php`][TaskHandlerFactory.php]

### Signature

```php
use Hooks;
use SMW\Store;

Hooks::register( 'SMW::Admin::TaskHandlerFactory', function( &$taskHandlers, Store $store, $outputFormatter, $user ) {

	// Instance of TaskHandler
	// $taskHandlers[] = new FooTaskHandler();

	return true;
} );
```

## See also

- See the [`ElasticFactory.php`][ElasticFactory.php] for an implementation example

[TaskHandlerFactory.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Specials/Admin/TaskHandlerFactory.php
[ElasticFactory.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/ElasticFactory.php