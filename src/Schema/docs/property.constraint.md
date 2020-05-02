## Objective

The `PROPERTY_CONSTRAINT_SCHEMA` schema type defines constraint definitions that can be assigned to a property using the `Constraint schema` property.

### Naming convention

To easily identify pages that contain a constraint schema it is suggested to use `smw/schema:Constraint:...` as naming convention.

## Properties

- `type` defines the type and is fixed to `PROPERTY_CONSTRAINT_SCHEMA`
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
- [`single_value_constraint`][example.schema] (boolean) specifies that the property expects only a single value per assigned entity
- [`custom_constraint`][custom.constraint] (object) specifies non-schema specific custom constraints implementations
- [`non_negative_integer`][example.schema] (boolean) specifies that values are derived from integer with the minimum inclusive to be 0
- [`must_exists`][example.schema] (boolean) specifies that the annotated value must exist to be valid

### Extending constraints

- General introduction on how to extend a [constraint][extending.constraint]
- How to register a [custom constraint][custom.constraint] using the `custom_constraint` property

## Validation

`/data/schema/property-constraint-schema.v1.json`

[example.schema]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/constraint.schema.md
[custom.constraint]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.constraint.md
[extending.constraint]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.constraint.md
