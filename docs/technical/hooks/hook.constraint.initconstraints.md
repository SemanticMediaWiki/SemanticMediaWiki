## SMW::Constraint::initConstraints

* Since: 3.1
* Description: Hook to allow adding custom constraint checks for a `custom_constraint`.
* Reference class: [`ConstraintRegistry.php`][ConstraintRegistry.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::Constraint::initConstraints', function( $constraintRegistry ) {

	return true;
} );
```

[ConstraintRegistry.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Constraint/ConstraintRegistry.php


## See also

- [register.custom.constraint.md][register.custom.constraint]

[register.custom.constraint]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.constraint.md
