## Objective

The `PROPERTY_PROFILE_SCHEMA` schema type defines low level features sets for a property that assigns the schema using the declarative `Profile schema` property.

## Properties

- `type`
- `profile` identifies the section that contains option definitions
  - `sequence_map` to record the [sequence][SequenceMap] of values (an ordered list of values) for the property that has the schema assigned
  - `range_group` to define a list of arbitrary range definitions (only used in connection with the [faceted search][FacetedSearch] at the time of this writing)
  - `range_control` to define parameters used to control a range slider (only used in connection with the [faceted search][FacetedSearch] at the time of this writing)
- `tags` simple tags to categorize a schema

### Defining support for a sequence map

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

### Defining a range group

`range_group` is provided to describe a set of arbitrary range definitions where the identifier is a short text or message key to identify the group while the assigned "value" defines the range and requires `...` between the minimum and maximum value. `INF` indicates infinity and should be used when no minimum or maximum value is defined or available.

#### Number type

<pre>
{
    "type": "PROPERTY_PROFILE_SCHEMA",
    "profile": {
        "range_group": {
            "0 - 10 000": "0...10000",
            "10 001 - 100 000": "10001...100000",
            "100 001 - 500 000": "100001...500000",
            "500 001 - 1 000 000": "500001...1000000",
            "1 000 001 - 5 000 000": "1000001...5000000",
            "5 000 000+": "5000001...INF"
        }
    },
    "tags": [
        "visitors",
        "range group"
    ]
}
</pre>

#### Quantity type

<pre>
{
    "type": "PROPERTY_PROFILE_SCHEMA",
    "profile": {
        "range_group": {
            "1 km² - 150 km²": "1 km²...150 km²",
            "151 km² - 350 km²": "151 km²...350 km²",
            ...
            "12 251+ km²": "12251 km²...INF"
        }
    },
    "tags": [
        "area",
        "range group"
    ]
}
</pre>

#### Date type

<pre>
{
    "type": "PROPERTY_PROFILE_SCHEMA",
    "profile": {
        "range_group": {
            "Before 17th century": "INF...1599",
            "17th century": "1600...1699",
            "18th century": "1700...1799",
            "19th century": "1800...1899",
            "20th century": "1900...1999",
            "21st century": "2000...2099",
            "Civil War, 1861 - 1865": "1861...1865"
        }
    },
    "tags": [
        "date range",
        "century range",
        "range group"
    ]
}
</pre>

A special case for defining a date range group is using a relative marker such as `{{CURRENTTIME}}` or `{{-50 years}}` which replaces the range values with discrete values at the time of its application. For example:

- `{{CURRENTTIME}}` is replaced by the current date
- `{{-50 years}}` is replaced by the current date minus (`-`) 50 years
- `{{+5 week}}` is replaced by the current date plus (`+`) 5 weeks

<pre>
{
    "type": "PROPERTY_PROFILE_SCHEMA",
    "profile": {
        "range_group": {
            "within last 50 years": "{{-50 years}}...{{CURRENTTIME}}",
            "within the next 5 weeks": "{{CURRENTTIME}}...{{+5 week}}"
        }
    },
    "tags": [
        "date range",
        "range group"
    ]
}
</pre>

### Range control

- `min_interval` minimum interval between ranges
- `step_size` size of steps applied for the next range
- `precision` applied precision used to select a range
- `uncertainty` `±` applied to a min/max value

<pre>
{
    "type": "PROPERTY_PROFILE_SCHEMA",
    "profile": {
        "range_control": {
            "min_interval": 10,
            "step_size": 10,
            "precision": 3,
            "uncertainty": 0.5
        }
    },
    "tags": [
        "area",
        "range group"
    ]
}
</pre>

## Validation schema

`/data/schema/property-profile-schema.v1.json`

[FacetedSearch]:https://www.semantic-mediawiki.org/wiki/Faceted_search
[SequenceMap]:https://www.semantic-mediawiki.org/wiki/Help:Sequence_map