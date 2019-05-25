## Objective

The `PROPERTY_GROUP_SCHEMA` schema type defines property groups to help structure the browsing interface.

## Properties

- `type`
- `manifest_version`
- `groups` identifies the section that contains a group definition
    - `group_name` group identifier and canonical group label
    - `message_key` contains a `key` that can be translated and replace the canonical group label
    - `properties` list of properties keys assigned to the group
- `tags` simple tags to categorize a schema

### Example

<pre>
{
    "type": "PROPERTY_GROUP_SCHEMA",
    "groups": [
        {
            "group_name": "My properties",
            "message_key": "smw-...",
            "properties": [
                "MY_PROPERTY"
            ]
        }
    ],
    "tags": [
        "group",
        "property groups"
    ]
}
</pre>

## Validation

`/data/schema/property-group-schema.v1.json`