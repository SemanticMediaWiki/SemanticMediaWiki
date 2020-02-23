## Objective

The `PROPERTY_GROUP_SCHEMA` schema type defines property groups to help structure the browsing interface.

## Properties

- `type`
- `manifest_version`
- `description` describes the entire group schema
- `groups` identifies the section that contains a group definitions
    - `..._group` identifies an individual group (the name has to end with `_group`)
      - `canonical_name` canonical group label
      - `message_key` contains a `key` that can be translated and replaces the canonical group label (if available)
      - `property_keys` list of property keys assigned to the group
- `tags` simple tags to categorize a schema

### Example

<pre>
{
    "type": "PROPERTY_GROUP_SCHEMA",
    "groups": {
        "x_group": {
            "canonical_name": "My properties X",
            "message_key": "smw-...",
            "property_keys": [
                "MY_PROPERTY_X"
            ]
        },
        "y_group": {
            "canonical_name": "My properties Y",
            "message_key": "smw-...",
            "property_keys": [
                "MY_PROPERTY_Y"
            ]
        }
    },
    "tags": [
        "group",
        "property groups"
    ]
}
</pre>

## Validation

`/data/schema/property-group-schema.v1.json`
