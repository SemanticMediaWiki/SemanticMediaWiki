```php
$descriptionFactory = new DescriptionFactory();

// Equivalent to [[Category:Foo]]
$classDescription = $descriptionFactory->newClassDescription(
	new DIWikiPage( 'Foo', NS_CATEGORY )
);
```

```php
// Equivalent to [[:+]]
$namespaceDescription = $descriptionFactory->newNamespaceDescription(
	NS_MAIN
);
```

```php
// Equivalent to [[Foo::+]]
$anyValueForSomeProperty = $descriptionFactory->newSomeProperty(
	new DIProperty( 'Foo' ),
	new ThingDescription()
);
```

```php
// Equivalent to [[:+]][[Category:Foo]][[Foo::+]]
$description = $descriptionFactory->newConjunction( array(
	$namespaceDescription,
	$classDescription,
	$anyValueForSomeProperty
) );
```

```php
// Equivalent to [[Category:Foo]] OR [[Foo::+]]
$description = $descriptionFactory->newDisjunction( array(
	$classDescription,
	$anyValueForSomeProperty
) );
```