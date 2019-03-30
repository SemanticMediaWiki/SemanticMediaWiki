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
        "allowed_namespaces": [
            "NS_USER"
        ]
    },
    "tags": [
        "property constraint"
    ]
}
</pre>

### Constraint properties

- `allowed_namespaces` (array) specifies allowed namespaces

### Extending constraint properties

For details, please see the [extending.constraint.md](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/extending.constraint.md) document.

## Validation

`/data/schema/property-constraint-schema.v1.json`
