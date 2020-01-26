The [`HookDispatcher`][dispatcher] is provided to inject a hook event handler into a class that triggers a specific hook event with the objective to isolate the MediaWiki `Hooks::run` static caller from a class instance.

The removal of the `Hooks::run` static caller from an individual class follows mainly the problem of leaking global state into an instance which would persist during testing and hereby may alter results in a manner unpredictable based on hooks enabled at the time of the test run.

### Register and trigger a hook event

#### HookDispatcher

Extend the [`HookDispatcher`][dispatcher] class with a particular method that is considered the public interface to trigger a hook event.

```php
class HookDispatcher {

	/**
	 * @see ...
	 * @since 3.2
	 *
	 * @param $bar
	 */
	public function onChangingSomething( $bar ) {
		Hooks::run( 'SMW::Fake::ChangingSomething', [ $bar ] );
	}

}
```

#### HookDispatcherAwareTrait

The [`HookDispatcherAwareTrait`][trait] has been introduced to help extend a class that is expected to trigger a specific hook event.

It requires to inject the `HookDispatcher` upon creation of an instance of that class (which should be done using a factory) hereby removes global state that would otherwise be leaking into the instance via `Hooks::run`.

```php
use SMW\MediaWiki\HookDispatcherAwareTrait;

class Foo {

	use HookDispatcherAwareTrait;

	public function doSomethingAndTriggerAnEvent( $bar ) {

		// Trigger the hook event
		$this->hookDispatcher->onChangingSomething( $bar );
	}

}
```
```php
use SMW\Services\ServicesFactory;

$servicesFactory = ServicesFactory::getInstance();

$foo = new Foo();

$foo->setHookDispatcher(
	$servicesFactory->getHookDispatcher()
);

$foo->doSomethingAndTriggerAnEvent( 'abc' );
```

## List of hooks

A list of [hook events][hook-list] provided by Semantic MediaWiki to help users to extend its core functionality.

[hook-list]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks.md
[dispatcher]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/HookDispatcher.php
[trait]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/HookDispatcherAwareTrait.php