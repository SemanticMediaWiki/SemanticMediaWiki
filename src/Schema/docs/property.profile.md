## Objective

The `PROPERTY_PROFILE_SCHEMA` schema type defines low level features sets for a property that assigns the schema using the declarative `Profile schema` property.

## Properties

- `type`
- `profile` identifies the section that contains option definitions
    - `sequence_map` to record the sequence of values (an ordered list of values) for the property that has the schema assigned
- `tags` simple tags to categorize a schema

### Example

<pre>
{
    "type": "PROPERTY_PROFILE_SCHEMA",
    "profile": {
        "sequence_map": true
    },
    "tags": [
        "option",
        "property option",
        "property profile"
    ]
}
</pre>

## Validation

`/data/schema/property-profile-schema.v1.json`