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
        "class constraint",
        "..."
    ]
}
</pre>

### Constraint properties

- [`mandatory_properties`][example.schema.mandatory] (array) specifies mandatory properties
- [`shape_constraint`][example.schema.shape] (array) specifies shapes of properties and dependent characteristics
  - `property` specifies the related property
  - `property_type` specifies expected type of the property
  - `max_cardinality` specifies the maximum number of values a property can contain for the given context
  - `min_textlength` specifies the minimum length of the characters expected for values assigned to the property
- [`custom_constraint`][custom.constraint] (object) specifies non-schema specific custom constraints implementations

### Extending constraints

- General introduction on how to extend a [constraint][extending.constraint] in Semantic MediaWiki
- How to register a [custom constraint][custom.constraint] using the `custom_constraint` property

## Validation

`/data/schema/class-constraint-schema.v1.json`

[example.schema.mandatory]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/constraint.schema.md#mandatory-properties
[example.schema.shape]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/constraint.schema.md#shape-constraint
[custom.constraint]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/register.custom.constraint.md
[extending.constraint]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.constraint.md