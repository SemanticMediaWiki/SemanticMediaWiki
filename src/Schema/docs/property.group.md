## Objective

The `PROPERTY_GROUP_SCHEMA` schema type defines property groups to help structure the browsing interface.

## Properties

- `type`
- `manifest_version`
- `groups` identifies the section that contains a group definition
  - `my_group` group identifier
    - `group_name` canonical group label
    - `msg_key` contains a `key` that can be translated and replace the canonical group label
    - `property_list` list of properties keys assigned to the group
- `tags` simple tags to categorize a schema

### Example

<pre>
{
    "type": "PROPERTY_GROUP_SCHEMA",
    "manifest_version": 1,
    "groups": {
        "my_group": {
            "group_name": "My properties",
            "msg_key": "smw-...",
            "property_list": [
                "MY_PROPERTY"
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