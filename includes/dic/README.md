## Overview
A basic dependency injection framework that enables object injection for immutable or service objects in Semantic MediaWiki. The current framework supports:

* Use of predefined object definitions (see SharedDependencyContainer)
* Use of eager or lazy loading of objects
* Use of "named" constructor arguments
* Registration of multiple container
* Prototypical (default) and singleton scope

This framework does not deploy a DependencyResolver (trying to automatically resolve dependencies) instead injections will have to be declarative within a dependency requestor.

### In a nutshell
A dependency object (such as service or immutable object etc.) contains specification about how an object ought to be build.

A dependency container (or object assembler) bundles specification and definitions of objects that can be used independently for instantiation from an invoking class.

A dependency builder uses available object specifications (provided by a dependency container) to instantiate an requested object.

For a more exhaustive description about dependency injection, see [Forms of Dependency Injection][mf] and [What is Dependency Injection?][fp].

## Usage
```php
/**
 * Using traditional instantiation
 */
$mightyObject = new ElephantineObject();

/**
 * Using the help of a DependencyBuilder for object instantiation
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

## Components

### DependencyObject
DependencyObject is an interface that specifies a method to resolve an object specification and can be used as free agent.

```php
class QuuxDependencyObject implements DependencyObject {

	public function resolveObject( DependencyBuilder $builder ) {
		return new Quux( new Foo( $builder->newObject( 'Bam' ) );
	}

}

// Register with a container (Lazy loading)
$container->registerObject( 'Quux', new QuuxDependencyObject() );
```

```php
// Register with a container (Lazy loading)
$container->registerObject( 'Quux', function( DependencyBuilder $builder ) {
	return new Quux( new Foo( $builder->newObject( 'Bam' ) );
} );
```
### DependencyContainer
DependencyContainer is an interface that specifies method to register DepencyObjects. BaseDependencyContainer implements the DependencyContainer and declares methods to retrieve and store object definitions. 

EmptyDependencyContainer is an empty container that extends BaseDependencyContainer while SharedDependencyContainer implements most common object definitions used during Semantic MediaWiki's life cycle.

```php
class FooDependencyContainer extends BaseDependencyContainer {

	/**
	 * Eager loading object
	 */
	$this->Foo = new Foo();

	/**
	 * Eager loading object
	 */
	$this->registerObject( 'Bar', new \stdClass );

	/**
	 * Lazy loading using a Closure
	 */
	$this->Baz = function ( DependencyBuilder $builder ) {
		return new Baz( $builder->newObject( 'Bar' ) );
	} );

	/**
	 * Lazy loading using a Closure
	 */
	$this->registerObject( 'Bam', function ( DependencyBuilder $builder ) {
		return new Bam( $builder->newObject( 'Baz' ) );
	} );

	/**
	 * Lazy loading using a DependencyObject
	 */
	$this->registerObject( 'Quux', new QuuxDependencyObject() );

}
```

### DependencyBuilder
DependencyFactory an interface that specifies a method to create a new object and with DependencyBuilder as interface specifying methods to handle access to container and objects.

SimpleDependencyBuilder implements the DependencyBuilder to enable access to objects and other invoked arguments.

```php
$builder = new SimpleDependencyBuilder( new FooDependencyContainer() );

/**
 * Accessing objects
 */
$builder->newObject( 'Foo' );
$builder->Foo();

/**
 * Accessing objects with arguments
 */
$builder->addArgument( 'Foo', $builder->newObject( 'Foo' ) )->newObject( 'Bar' );
$builder->Bar( array( 'Foo' => $builder->newObject( 'Foo' ) ) );
$builder->newObject( 'Bar', array( 'Foo' => $builder->newObject( 'Foo' ) ) );

/**
 * Deferred object registration using the builder
 */
$builder->getContainer()->registerObject( 'Xyzzy', new Xyzzy() );
$builder->newObject( 'Xyzzy' );

```

### DependencyInjector
DependencyRequestor is an interface specifying access to a DependencyBuilder within a client that requests dependency injection and DependencyInjector (implements DependencyRequestor) provides convenience access.

```php
class FooClass extends DependencyInjector { ... }

$fooClass = new FooClass( ... )
$fooClass->setDependencyBuilder( new SimpleDependencyBuilder() );

$fooClass->getDependencyBuilder()->newObject( 'Bar' );
```

### Scope
The scope defines the lifetime of an object and if not declared otherwise an object is alwasy create with a prototypical scope.
* SCOPE_PROTOTYPE (default) each injection or call to the newObject() method will result in a new instance
* SCOPE_SINGLETON  scope will return the same instance over the lifetime of a request

```php
$container = new EmptyDependencyContainer();

/**
 * Specify a SCOPE_PROTOTYPE
 */
$container->registerObject( 'Foo', function ( return new Foo() ) { ... } )
$container->registerObject( 'Foo', new Foo() )

$container->registerObject( 'Foo', function ( return new Foo() ) { ... }, DependencyObject::SCOPE_PROTOTYPE )

/**
 * Specify a SCOPE_SINGLETON
 */
$container->registerObject( 'Foo', function ( return new Foo() ) { ... }, DependencyObject::SCOPE_SINGLETON )
$container->registerObject( 'Foo', new Foo(), DependencyObject::SCOPE_SINGLETON )

/**
 * Adjust the object scope during build process
 */
$builder->setScope( DependencyObject::SCOPE_SINGLETON )->newObject( 'Foo' )
$builder->setScope( DependencyObject::SCOPE_PROTOTYPE )->Foo()
```

## Example
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
class ElephantineChocolateRequestor implements DependencyInjector {

	public function get( Chocolate $chocolate ) {
		return $this->getDependencyBuilder()
			->addArgument( 'Chocolate', $chocolate )
			->newObject( 'ElephantineObject' );
	}

}

/**
 * Client implementation
 */
$elephantineChocolate = new ElephantineChocolateRequestor();

$elephantineChocolate->setDependencyBuilder(
	new SimpleDependencyBuilder( new ElephantineDependencyContainer() )
);

/* @var ElephantineObject $mightyObject */
$chocolateObject = $elephantineChocolate->get( new OuterRimChocolate() )
```

[mf]: http://www.martinfowler.com/articles/injection.html#FormsOfDependencyInjection  "Forms of Dependency Injection"
[fp]: http://fabien.potencier.org/article/11/what-is-dependency-injection "What is Dependency Injection?"