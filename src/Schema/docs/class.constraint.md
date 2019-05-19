## Objective

The `CLASS_CONSTRAINT_SCHEMA` schema type defines constraint definitions that can be assigned to a class (aka. category) using the `Constraint schema` property.

### Naming convention

To easily identify pages that contain a constraint schema it is suggested to use `smw/schema:Constraint:...` as naming convention.

## Properties

- `type` defines the type and is fixed to `CLASS_CONSTRAINT_SCHEMA`
- `constraints` the section that contains constraints definitions
- `tags` simple tags to categorize a schema

### Example

<pre>
{
    "type": "CLASS_CONSTRAINT_SCHEMA",
    "constraints": {
        ...
    },
    "tags": [
        "property constraint",
        "..."
    ]
}
</pre>

### Constraint properties

- `mandatory_properties` (array) specifies mandatory properties
- [`custom_constraint`][custom.constraint] (object) specifies non-schema specific custom constraints implementations

### Extending constraints

- General introduction on how to extend a [constraint][extending.constraint]
- How to register a [custom constraint][custom.constraint] using the `custom_constraint` property

## Validation

`/data/schema/class-constraint-schema.v1.json`

[example.schema]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/constraint.schema.md
[custom.constraint]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.constraint.md
[extending.constraint]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.constraint.md