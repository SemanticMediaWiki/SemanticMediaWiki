## Objective

The `SEARCH_FORM_SCHEMA` schema type defines forms used in the extended `Special:Search` profile.

## Properties

- `type`
- `tags` simple tags to categorize a schema
- `forms` defines the forms and fields to be displayed

### Example

<pre>
{
    "type": "SEARCH_FORM_SCHEMA",
    "forms": {
        "Foo": [],
    }
    "tags": [
        "search form"
    ]
}
</pre>

#### Form definition

- `type` requires `SEARCH_FORM_SCHEMA`
- `forms` defines a collection of forms
  - `Books and journals` as title of a form
    - `Has title` is a simple input field without any constraints
    - `Publication type` is a input field with additional attributes

<pre>
{
    "type": "SEARCH_FORM_SCHEMA",
    "forms": {
        "Books and journals": [
            "Has title",
            "Has author",
            "Has year",
            {
                "Publication type": {
                    "autocomplete": true,
                    "tooltip": "Some context to be shown ...",
                    "required": true
                }
            },
            {
                "Publisher": {
                    "autocomplete": true
                    "tooltip": "message-can-be-a-msg-key"
                }
            }
        ],
        "Media and files": [ ]
    }
}
</pre>

Fields can define attributes such as:

- `autocomplete` (true, false) whether the field should add an autocomplete function or not
- `tooltip` (text or msg key) shows a tooltip with either a text or retrieves information from a message key
- `placeholder` (text) shown instead of the property name
- `required` (true, false) whether the field input is required before submitting or not
- `type` (HTML5) preselect a specific type field

#### Default form

`default_form` to define a default form that is displayed when no other form was preselected.

<pre>
{
    "type": "SEARCH_FORM_SCHEMA",
    "default_form": "Books and journals",
    ...
}
</pre>

#### Term parser

The `term_parser` prefix can be used to shorten the input cycle and summarize frequent properties so that a user can write:
 - `(in:foobar || phrase:foo bar) lang:fr` instead of
 - `<q>[[in:foobar]] || [[phrase:foo bar]]</q><q>[[Language code::fr]] OR [[Document language::fr]] OR [[File attachment.Content language::fr]] OR [[Has interlanguage link.Page content language::fr]]</q>`

<pre>
{
    "type": "SEARCH_FORM_SCHEMA",
    "term_parser": {
        "prefix": {
            "lang": [
                "Language code",
                "Document language",
                "File attachment.Content language",
                "Has interlanguage link.Page content language"
            ]
        }
    }
}
</pre>

Prefixes are only applicable (and usable as means the shorten the search term) from within the extended search form.

#### Namespaces

`namespaces` section defines namespaces to be preslected or hidden.

- `default_hide` hides the namespace box by default on the extended profile form
- `hidden` identifies namespaces that should be hidden from appearing in any SMW related form
- `preselect` assign a pre-selection of namespaces to a specific form

<pre>
{
    "type": "SEARCH_FORM_SCHEMA",
    "namespaces": {
        "default_hide": true,
        "hidden": [
           "NS_PROJECT",
           "NS_PROJECT_TALK"
        ],
        "preselect": {
           "Books and journals": [
                "NS_CUSTOM_BOOKS",
                "NS_FILE"
            ],
           "Media and files": [
                "NS_FILE"
            ]
        }
    }
}
</pre>

#### Descriptions

Describes a form and is shown at the top of the form fields to inform users about the intent of
the form.

<pre>
{
    "type": "SEARCH_FORM_SCHEMA",
    "descriptions": {
        "Books and journals": "Short description to be shown on top of a selected form"
    }
}
</pre>

## Validation

`/data/schema/search-form-schema.v1.json`