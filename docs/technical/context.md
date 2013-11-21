A context object (see [Encapsulate Context Pattern][ak]) collects commonly used service objects and encapsulate those objects within a single available instance.

## Components
* ContextResource describes an interface to access Store, Settings, and a DependencyBuilder context
* ContextAware describes an interface to access a context object
* ContextInjector describes an interface to inject an context object
* ExtensionContext implements the ContextResource interface
* EmptyContext implements the ContextResource interface, returning null objects

#### Example
```php
class Foo implements ContextAware {

	/** @var ContextResource */
	protected $context = null;

	/**
	 * @since 1.9
	 *
	 * @param ContextResource $context
	 */
	public function __construct( ContextResource $context = null ) {
		$this->context = $context;
	}

	public function withContext() {

		if ( $this->context === null ) {
			$this->context = new ExtensionContext();
		}

		return $this->context;
	}

	public function getBaz() {
		return $this->withContext()->getDependencyBuilder()->newObject( 'Baz' );
	}

}
```
```php
class Bar implements ContextAware, ContextInjector {

	/** @var ContextResource */
	protected $context = null;

	public function invokeContext( ContextResource $context ) {
		$this->context = $context;
	}

	public function withContext() {
		return $this->context;
	}

	public function getBaz() {
		return $this->withContext()->getDependencyBuilder()->newObject( 'Baz' );
	}

}
```
```php
$foo = new Foo( new ExtensionContext() );
$baz = $foo->getBaz();

$bar = new Bar();
$bar->invokeContext( new ExtensionContext() );
$baz = $bar->getBaz();
```

For information on "how to use" the DependencyBuilder, see [here](dic.md).

[ak]: http://accu.org/index.php/journals/246  "The Encapsulate Context Pattern"