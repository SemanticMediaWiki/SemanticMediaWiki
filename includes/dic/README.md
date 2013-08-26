## Overview
A basic dependency injection framework that enables object injection for immutable or service objects in Semantic MediaWiki.

A dependency injection container (or object assembler) bundles specification and definitions of objects (also known as service objects) that can be used independently for instantiation from an invoking class. The current framework supports:

* Use of predefined object definitions (see SharedDependencyContainer)
* Use of eager or lazy loading of objects
* Use of "named" constructor arguments
* Registration of multiple container
* Prototypical (default) and singleton scope

This framework does not deploy a DependencyResolver (trying to automatically resolve dependencies) instead injections will have to be declarative within a dependency requestor.

### Usage
```php
// Traditional instantiation
$mightyObject = new ElephantineObject();

/**
 * Using DependencyBuilder instantiation
 * @var ElephantineObject $mightyObject
 */
$mightyObject = $this->getDependencyBuilder()->newObject( 'ElephantineObject' ) or
$mightyObject = $this->getDependencyBuilder()->ElephantineObject()
```

When constructing objects using the DI framework we avoid using the “new” keyword to create objects and instead rely on a builder to resolve an object graph and its instantiation. The use of dependency injection and for that matter of a dependency injection framework can help:
* Reduce reliance on hard-coded dependencies
* Achieve separation of concerns
* Improve mocking of objects

There are different ways to achieve dependency injection (constructor injection, setter injection, interface injection, or call time injection). The current framework divides its work into two components a builder (responsible for the actual instantiation of an object) and a container (accommodates object definitions).

The requesting client (class that consumes the dependency) is normally unaware of an injected container as it requires the builder to manage the object instantiation and for that reason a client requires the help of a builder provided by the DI framework.

The client has the choice either to implement the DependencyRequestor, extend the DependencyInjector for convenience (in both instances a builder itself becomes a dependency), or inject a builder using a constructor or setter (to reduce the DependencyRequestor interface dependency from a client).

Gaining independence and control over service object instantiation requires appropriate unit testing to ensure that injected containers do contain proper object definitions used within a requestor that will yield proper object instantiation.

### Scope
The scope defines the lifetime of an object and if not declared otherwise an object is alwasy create with a prototypical scope.
* SCOPE_PROTOTYPE (default) each injection or call to the newObject() method will result in a new instance
* SCOPE_SINGLETON  scope will return the same instance over the lifetime of a request

## DependencyContainer
```
DependencyObject
	-> DependencyContainer
		-> BaseDependencyContainer
			-> EmptyDependencyContainer
			-> SharedDependencyContainer
```

A dependency container bundles specification and definitions of objects with each object being responsible to specify an object graph and its internal dependencies. The current framework specifies:
* DependencyObject an interface that specifies a method to register a dependency object
* DependencyContainer an interface that specifies methods to retrieve and store object definitions
* BaseDependencyContainer implements the DependencyContainer
* EmptyDependencyContainer an empty container that extends BaseDependencyContainer.
* SharedDependencyContainer implements common object definitions used during Semantic MediaWiki's life cycle.

## DependencyBuilder
```
DependencyFactory
	-> DependencyBuilder
		-> SimpleDependencyBuilder
```
* DependencyFactory an interface that specifies a method to create a new object
* DependencyBuilder an interface specifies methods to handle injection container and objects
* SimpleDependencyBuilder implementing the DependencyBuilder to enable access to DependencyContainer objects and other invoked arguments

## DependencyInjector
```
DependencyRequestor
	-> DependencyInjector
```
* DependencyRequestor an interface specifying access to a DependencyBuilder within a client that requests dependency injection
* DependencyInjector an abstract class that implements the DependencyRequestor to enable convenience access to an injected DependencyBuilder

## Examples
```php
/**
 * Object specifications
 */
class ElephantineDependencyContainer extends BaseDependencyContainer {

	$this->registerObject( 'Candy', function ( DependencyBuilder $builder ) {
		return new Candy();
	}, DependencyObject::SCOPE_SINGLETON );

	$this->registerObject( 'ElephantineObject', function ( DependencyBuilder $builder ) {
		return new ElephantineObject(
			new Foo( $builder->newObject( 'Candy' ) ),
			$builder->getArgument( 'Chocolate' )
		);
	}, DependencyObject::SCOPE_PROTOTYPE );

}

/**
 * Object request handler
 */
class ElephantineRequestor implements DependencyInjector {

	public function get( Chocolate $chocolate ) {
		return $this->getDependencyBuilder()
			->addArgument( 'Chocolate', $chocolate )
			->newObject( 'ElephantineObject' );
	}

}

/**
 * Client implementation
 */
$elephantine = new ElephantineRequestor();

$elephantine->setDependencyBuilder(
	new SimpleDependencyBuilder( new ElephantineDependencyContainer() )
);

/* @var ElephantineObject $mightyObject */
$mightyObject = $elephantine->get( new OuterRimChocolate() )
```

### DependencyContainer
#### Register an object (eager loading)
```php
$container = new EmptyDependencyContainer();

$container->Title = new Title();
$container->registerObject( 'Foo', new \stdClass );
```

### Register an object (lazy loading)
```php
$container = new EmptyDependencyContainer();

$container->Foo = function ( DependencyBuilder $builder ) {
  return new Foo( $builder->newObject( 'Bar' ) );
} );

$container->registerObject( 'DIWikiPage', function ( DependencyBuilder $builder ) {
  return DIWikiPage::newFromTitle( $builder->getArgument( 'Title' ) );
} );
```

### SimpleDependencyBuilder
#### Access objects
```php
$builder = new SimpleDependencyBuilder( $container );

$builder->newObject( 'Foo' );
$builder->Foo();
```

#### Access objects with arguments
```php
$builder = new SimpleDependencyBuilder( $container );

$builder->addArgument( 'Title', $builder->newObject( 'Title' ) );
$builder->newObject( 'DIWikiPage' );

$builder->DIWikiPage( $builder->newObject( 'Title' ) );
$builder->newObject( 'DIWikiPage', array( $builder->Title() ) );
```

### Deferred object registration using the builder
```php
$builder = new SimpleDependencyBuilder( $container );

$builder->getContainer()->registerObject( 'Bar', new Fruits() );
$builder->newObject( 'Bar' );
```

### Specifying object scope
#### Specify object scope (SCOPE_PROTOTYPE)
```php
$container = new EmptyDependencyContainer();

$container->registerObject( 'Foo', function ( return new Foo() ) { ... } )
$container->registerObject( 'Foo', new Foo() )

$container->registerObject( 'Foo', function ( return new Foo() ) { ... }, DependencyObject::SCOPE_PROTOTYPE )
```

####  Specify object scope (SCOPE_SINGLETON)
```php
$container = new EmptyDependencyContainer();

$container->registerObject( 'Foo', function ( return new Foo() ) { ... }, DependencyObject::SCOPE_SINGLETON )
$container->registerObject( 'Foo', new Foo(), DependencyObject::SCOPE_SINGLETON )
```

#### Change object scope during build process
```php
$builder = new SimpleDependencyBuilder( $container );

$builder->setScope( DependencyObject::SCOPE_SINGLETON )->newObject( 'ElephantineObject' )
$builder->setScope( DependencyObject::SCOPE_PROTOTYPE )->ElephantineObject()
```

### Using the DependencyInjector
```php
class FooClass extends DependencyInjector { ... }

$fooClass = new FooClass( ... )
$fooClass->setDependencyBuilder( new SimpleDependencyBuilder() );

$fooClass->getDependencyBuilder()->newObject( 'Bar' );
```