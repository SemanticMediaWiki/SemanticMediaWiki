## Register new constraint

This example shows how to register a custom constraint.

### SMW::Constraint::initConstraints

```php
use Hooks;
use Foo\FooConstraint;

Hooks::register( 'SMW::Constraint::initConstraints', function ( $constraintRegistry ) {

	// Defined the constraint name and assign a class that interprets the
	// content of it
	$constraintRegistry->registerConstraint( 'foo_constraint', FooConstraint::class );

	return true;
} );
```

Assigning a non `Constraint` class or a callable that doesn't return a `Constraint` instance will throw an exception.

### Constraint representation

```php
use SMW\Constraint\Constraint;

class FooConstraint implements Constraint {

	private $hasViolation = false;

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasViolation() {
		return $this->hasViolation;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getType() {
		return Constraint::TYPE_INSTANT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function checkConstraint( array $constraint, $dataValue ) {

		$this->hasViolation = false;

		// Do the necessary checks

		// `$constraint` contains the details of what the schema has defined
		// and should be used to interpret the data available and identify any
		// violations

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

The `custom_constraint` property is an `object` in the schema and is reserved for custom defined constraints using above outlined invocation and hook. The interpretation of what `custom_constraint` should contain and how those values of the schema are interpret are in the solely hand of the implementor.

The validation schema only checks whether `custom_constraint` is an object or not, any further validation of what how the object is structured or should contain needs to be implemented using the assigned `Constraint` class.

```json
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
