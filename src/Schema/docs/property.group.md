## Objective

The `PROPERTY_GROUP_SCHEMA` schema type defines property groups that help structure the browsing interface of a page.

## Properties

The structure of this schema is defined by the following properties:

- `type` – defindes the schema type
- `manifest_version` – sets the version of the schema type
- `description` – describes the entire group schema
- `groups`– identifies the section that contains group definitions
  - `..._group` – identifies an individual group (the name has to end with `_group`)
    - `canonical_name` – sets the canonical lable for the group
    - `message_key` – sets a system message key that can be used for translation and replaces the canonical label for the group if specified
    - `property_keys` – sets the property keys assigned to the group
- `tags` – sets simple tags to categorize a schema

## Example

<pre>
{
    "type": "PROPERTY_GROUP_SCHEMA",
    "groups": {
        "x_group": {
            "canonical_name": "My properties X",
            "message_key": "smw-property-group-label-...",
            "property_keys": [
                "MY_PROPERTY_XA",
                "MY_PROPERTY_XB"
            ]
        },
        "y_group": {
            "canonical_name": "My properties Y",
            "message_key": "smw-property-group-label-...",
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

## Validation schema

The structure of this schema is validated by the following definition file for the schema structure:

`/data/schema/property-group-schema.v1.json`
