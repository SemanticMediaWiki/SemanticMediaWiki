* Since: 3.1
* Description: Hook to register additional event listeners.
* Reference class: [`EventListenerRegistry.php`][EventListenerRegistry.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use Onoi\EventListener\EventListener;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Event::RegisterEventListeners', function( EventListener $eventListener ) {

	// $eventListener->registerCallback( 'FooEvent' , [ $this, 'onFooEvent' ] );

	return true;
} );
```

[EventListenerRegistry.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/EventListenerRegistry.php