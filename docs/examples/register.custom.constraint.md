The following example shows how to register a custom constraint using the `custom_constraint` property.

## Register a custom constraint

The `custom_constraint` property is an `object` in the schema and is reserved for custom defined constraints using above outlined invocation and hook. The interpretation of what `custom_constraint` should contain and how those values of the schema are interpret are in the solely hand of the implementor.

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
The validation schema only checks whether `custom_constraint` is an object or not, any further validation of what and how the object is structured is not part of the schema validation, the assigned `Constraint` class should handle any inconsistencies with an invalid constraint definition.

### Hook registration

```php
use Hooks;
use Foo\FooConstraint;

Hooks::register( 'SMW::Constraint::initConstraints', function ( $constraintRegistry ) {

	// Declares the constraint identifier and assigns a class that interprets the
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

## Other examples

For example, when implementing a `start_end_constraint` with an identifier `greater_than`, the `greater_than` property may expect an array with the registered class being responsible for interpreting what `greater_than` means in the context of the given array such as "the first element (e.g. property `Event end`) needs to be greater than the second element (e.g. property `Event start`)".


```json
{
	"type": "CLASS_CONSTRAINT_SCHEMA",
	"constraints": {
		"custom_constraint": {
			"start_end_constraint": {
				"greater_than": ["Event end", "Event start"]
			}
		}
	},
	"tags": [
		"class constraint",
		"custom constraint"
	]
}
```

```php
use SMW\Constraint\Constraint;
use SMW\SemanticData;

class StartEndConstraint implements Constraint {

	private $hasViolation;

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

		if ( !$dataValue instanceof \SMWDataValue ) {
			throw new RuntimeException( "Expected a DataValue instance!" );
		}

		foreach ( $constraint as $key => $v ) {
			if ( $key === 'start_end_constraint' ) {
				$this->start_end_constraint( $v, $dataValue );
			}
		}
	}

	private function start_end_constraint( $constraint, $dataValue ) {

		if ( !isset( $constraint['greater_than'] ) ) {
			return;
		}

		// Interpret the array structure
		$end = $constraint['greater_than'][0];
		$start = $constraint['greater_than'][1];

		$semanticData = $dataValue->getCallable( SemanticData::class )();

		$s = $semanticData->getPropertyValues(
			\SMW\DIProperty::newFromUserLabel( $start )
		);

		$e = $semanticData->getPropertyValues(
			\SMW\DIProperty::newFromUserLabel( $end )
		);
	}
```

## See also

- [hook.constraint.initconstraints.md][hook.constraint.initconstraints]

[hook.constraint.initconstraints]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.constraint.initconstraints.md
