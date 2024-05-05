## SMW::Admin::RegisterTaskHandlers

* Since: 3.0
* Description: Hook allows to extend available `TaskHandler` for the display by the `Special:SemanticMediaWiki`
* Reference class: [`TaskHandlerRegistry.php`][TaskHandlerRegistry.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\Store;
use SMW\MediaWiki\Specials\Admin\TaskHandlerRegistry;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Admin::RegisterTaskHandlers', function( TaskHandlerRegistry $taskHandlerRegistry, Store $store, $outputFormatter, $user ) {

	// Instance of TaskHandler
	// $taskHandlerRegistry->registerTaskHandler( new FooTaskHandler() );

	return true;
} );
```

## See also

- See the [`ElasticFactory.php`][ElasticFactory.php] for an implementation example

[TaskHandlerRegistry.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Specials/Admin/TaskHandlerRegistry.php
[ElasticFactory.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/ElasticFactory.php