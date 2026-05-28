Semantic MediaWiki triggers its own hook events through MediaWiki's [`HookContainer`][hookcontainer], the injectable service introduced in MediaWiki 1.35. Injecting `HookContainer` into a class avoids leaking global state from the legacy `Hooks::run` static caller, keeping hook firings isolated and mockable in tests.

### Register and trigger a hook event

Inject `HookContainer` into the class that fires the hook (constructor injection is preferred; setter injection is also supported where the consumer is created via a factory or service wiring).

```php
use MediaWiki\HookContainer\HookContainer;

class Foo {

	public function __construct( private HookContainer $hookContainer ) {
	}

	public function doSomethingAndTriggerAnEvent( $bar ): void {
		$this->hookContainer->run( 'SMW::Fake::ChangingSomething', [ $bar ] );
	}

}
```

In `ServiceWiring.php` or any factory, obtain `HookContainer` from `MediaWikiServices`:

```php
use MediaWiki\MediaWikiServices;

$foo = new Foo(
	MediaWikiServices::getInstance()->getHookContainer()
);

$foo->doSomethingAndTriggerAnEvent( 'abc' );
```

For hooks that take arguments by reference, pass them with `&` inside the args array:

```php
$this->hookContainer->run( 'SMW::Fake::ChangingSomething', [ $title, &$mutableValue ] );
```

In tests, mock `HookContainer` and assert against the `run` method:

```php
$hookContainer = $this->createMock( HookContainer::class );
$hookContainer->expects( $this->once() )
	->method( 'run' )
	->with( 'SMW::Fake::ChangingSomething', [ $bar ] );
```

## List of hooks

A list of [hook events][hook-list] provided by Semantic MediaWiki to help users extend its core functionality.

[hook-list]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks.md
[hookcontainer]: https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/core/+/refs/heads/master/includes/HookContainer/HookContainer.php
