## Objective

The `FACETEDSEARCH_PROFILE_SCHEMA` schema type is used to define a profile that alters the appearance and behaviour of `Special:FacetedSearch`.

For example, an individual profile can be used by a specific user group or members of a project that require extra search fields or different collections of explorational queries.

## Properties

- `type` requires `FACETEDSEARCH_PROFILE_SCHEMA`
- `profiles` identifies the section that contains a list of defined profiles
- `tags` simple tags to categorize a schema

### Example

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": { ... },
        "bar_profile": { ... },
        "..._profile": { ... }
    }
}
</pre>

### Default profile

The `default_profile` is deployed with Semantic MediaWiki and cannot be modified (#4642) and contains default attributes to be used when no other profile is available. In case where users added their own profile, the property `default_profile` can be used to define which profile should be used as default for all users.

A logged-in user has also the possibility to define a personal preference for a profile which is selected as default when s(he) is starting to use `Special:FacetedSearch`.

The naming convention (enforced by the validation schema) for a profile is `*_profile`. It is possible to create one large page to contain various profiles or profiles can be spread across different pages using the `FACETEDSEARCH_PROFILE_SCHEMA` type. In any event, the profile name `..._profile` has to be unique for all `FACETEDSEARCH_PROFILE_SCHEMA` definitions.

The property `default_profile` should only be used once for the entire collection of profiles because when merging profiles from different pages into one coherent list, the first page that defines `default_profile` occupies the attributive value.

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "default_profile": "foo_profile",
    "profiles": {
        "foo_profile": { ... },
        "bar_profile": { ... }
    }
}
</pre>

### Debug output

To get information about the actual query that is been executed as part of the filtering process, the `debug_output` property can be added to a profile to generate relevant information.

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "debug_output": true
        }
    }
}
</pre>

### Theme

The `theme` property can be used to define a different CSS theme other than the `default-theme` that gets added to major HTML elemnts to allow users to modify the look and feel of the Faceted search without having to change the source code. The `theme` property enforces a naming covention and requires a `*-theme` pattern. An exnaple for a different theme can be found in the deployed CSS file and is called `light-theme`.

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "theme": "foo-theme"
        }
    }
}
</pre>

### Filters

#### Hierarchy tree

The `hierarch_tree` option for the `category_filter` and `property_filter` supports the generation of a hierarchy tree for the selected entities of the executed query to help visualize and comprehend dependencies between categories (or properties) with their children. Generating the tree requires an additional SQL query and is the reason the option isn't enabled by default.

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "filters": {
                "category_filter": {
                    "hierarchy_tree": true
                }
            }
        }
    }
}
</pre>

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "filters": {
                "property_filter": {
                    "hierarchy_tree": true
                }
            }
        }
    }
}
</pre>

#### Condition field

The `condition_field` attribute for the `value_filter` is provided to influence the conditional preference of `OR`, `AND`, or `NOT` for a set of value filters.

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "filters": {
                "value_filter": {
                    "condition_field": true
                }
            }
        }
    }
}
</pre>

#### Range group filter preference

A range group specifies a pair of value ranges that includes a contextual description together with the range definition. The textual description can be a simple text string or a MediaWiki message key to provide a translatable representation.

`range_group_filter_preference` can be either set to `true` which then will check each property for a possible `property profile` that contains a `range_group` definition or `range_group_filter_preference` can list a sot of properties to be checked exclusively.

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "filters": {
                "value_filter": {
                    "filter_type": {
                        "range_group_filter_preference": true
                    }
                }
            }
        }
    }
}
</pre>

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "filters": {
                "value_filter": {
                    "filter_type": {
                        "range_group_filter_preference": [
                            "Has area",
                            "Visitors"
                        ]
                    }
                }
            }
        }
    }
}
</pre>

Properties that should use a `range_group_filter_preference` require to assign a `Profile schema` and define a [property profile][property.profile] that contains a `range_group` definition.

### Extra fields

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "profiles": {
        "foo_profile": {
            "search": {
                "extra_fields": {
                    "default_collapsed": true,
                    "field_list": {
                        "x_field": {
                            "label": "x-label",
                            "message_key": "smw-...",
                            "field_type": "",
                            "property": "",
                            "autocomplete": false
                        }
                    }
                }
            }
        }
    }
}
</pre>

### Exploration section

The `exploration_section` is provided to define a collection of predetermined queries and is shown to users of `Special:FacetedSearch` when no other query has been selected. For example, it provides users of a certain profile with "short cuts" to queries often used for a specific use case or project.

<pre>
{
    "type": "FACETEDSEARCH_PROFILE_SCHEMA",
    "default_profile": "x1_profile",
    "profiles": {
        "foo_profile": {
            ""exploration: {
                "query_list": {
                    "npa_query": {
                        "query": "[[Category:National Park Service]]",
                        "label": "National Park Service",
                        "description": "Example query showing information about US national parks."
                    }
                }
            }
        }
    }
}
</pre>

## Validation schema

`/data/schema/facetedsearch-profile-schema.v1.json`

[property.profile]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/property.profile.md