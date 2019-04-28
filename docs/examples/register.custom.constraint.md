## Register new constraint

This example shows how to register a custom constraint.

### SMW::Constraint::initConstraints

```php
use Hooks;
use Foo\FooConstraint;

Hooks::register( 'SMW::Constraint::initConstraints', function ( $constraintRegistry ) {

	$constraintRegistry->registerConstraint(
		'foo_constraint',
		FooConstraint::class
	);

	return true;
};
```

### Constraint representation

```php
class FooConstraint implements Constraint {

	private $hasViolation = false;

	public function hasViolation() {
		return $this->hasViolation;
	}

	public function getType( $type ) {
		return Constraint::TYPE_INSTANT;
	}

	public function checkConstraint( array $constraint, $dataValue ) {

		$this->hasViolation = false;

		// Do the necessary checks

		...

		// Found a violation
		$this->hasViolation = true

		$dataValue->addErrorMsg(
			new ConstraintError( [
				'foo-violation-message-key'
			] )
		);

	}
}
```

### Usage

The `custom_constraint` property in the schema is reserved for custom defined constraints.

```
{
    "type": "PROPERTY_CONSTRAINT_SCHEMA",
    "constraints": {
        "custom_constraint": {
            "foo_constraint": true
        }
    },
    "tags": [
        "property constraint",
        "custom constraint"
    ]
}
```

## See also

- [hook.constraint.initconstraints.md][hook.constraint.initconstraints]

[hook.constraint.initconstraints]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.constraint.initconstraints.md
