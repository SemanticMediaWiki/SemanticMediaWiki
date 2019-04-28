### `src/Constraint`

#### Property constraint

To add a new constraint (let's say represented by `foo_contraint`) it is expected that:

- The `foo_contraint` property is registered with the validation schema (`property-constraint-schema.v1.json`) hereby defines its characteristics (array, boolean, pattern etc.)
- A new class is added that implements the [`Constraint`][constraint] interface and interprets the `foo_contraint` property together with the constraint boundaries
- The `foo_contraint` property and the newly created class are registered with the [`ConstraintRegistry`][constraint-registry]
- The newly created `Constraint` class is added to the [`ConstraintFactory`][constraint-factory] to define the object graph

When adding new constraints, please ensure that:

- Unit tests carefully test the expected behaviour
- The `ConstraintRegistryTest` and `ConstraintFactoryTest` are extended

#### TYPE_DEFERRED

When a constraint is expected to be expensive (in terms of performance, runtime) it should be postponed and be derived from the `DeferrableConstraint` class to ensure that those checks are run using the `DeferredConstraintCheckUpdateJob` hereby avoiding unnecessary resource hogging during a page view/GET process.

## See also

- How to register a [custom constraint](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.constraint.md)

[constraint]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Constraint/Constraint.php
[constraint-registry]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Constraint/ConstraintRegistry.php
[constraint-factory]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ConstraintFactory.php