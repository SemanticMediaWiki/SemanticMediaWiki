## Objective

The `PROPERTY_CONSTRAINT_SCHEMA` schema type defines constraint definitions that can be assigned to a property using the `Constraint schema` property.

### Naming convention

To easily identify pages that contain a constraint schema it is suggested to use `smw/schema:Constraint:...` as naming convention.

## Properties

- `type` defines the type and is fixed to `PROPERTY_CONSTRAINT_SCHEMA`
- `manifest_version`
- `constraints` the section that contains constraints definitions
- `tags` simple tags to categorize a schema

### Example

<pre>
{
    "type": "PROPERTY_CONSTRAINT_SCHEMA",
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

- `allowed_namespaces` (array) specifies allowed namespaces
- `unique_value_constraint` (boolean) specifies that values should be unique across the wiki, that the value is likely to be different (distinct) from all other items
- [`custom_constraint`][custom.constraint] (object) to be used to specify non-schema specific constraints that requrie an implementation using the `SMW::Constraint::initConstraints` hook
- [`non_negative_integer`][non_negative_integer] (boolean) specifies that values are derived from integer with the minimum inclusive to be 0

### Extending constraints

- General introduction on how to extend a [constraint][extending.constraint]
- How to register a [custom constraint][custom.constraint] using the `custom_constraint` property

## Validation

`/data/schema/property-constraint-schema.v1.json`

[non_negative_integer]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/constraint.schema.nonnegativeinteger.md
[custom.constraint]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.constraint.md
[extending.constraint]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.constraint.md