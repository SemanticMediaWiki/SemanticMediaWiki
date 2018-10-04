# SMWSearch

[SMWSearch](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch) is the `SearchEngine` interface to provide classes and functions to integrate Semantic MediaWiki with `Special:Search`.

It adds support for using #ask queries in the search input field and provides an extended search profile where user defined forms can empower users to find and match entities using property and value input fields.

## Extended search profile

![image](https://user-images.githubusercontent.com/1245473/43684698-7748fd76-9894-11e8-971f-3125892dc9ed.png)

### Defining forms and form fields

Form definitions are expected be created in the [rule namespace](https://www.semantic-mediawiki.org/wiki/Help:Rule) with the [`SEARCH_FORM_DEFINITION_RULE`](https://www.semantic-mediawiki.org/wiki/Help:Rule/Type/SEARCH_FORM_DEFINITION_RULE) as mandatory type to declare field constraints and validation checkpoints (see the `$smwgRuleTypes` setting) required by the type.

Definitions are structured using the JSON format and in the following example represent:

- `"type"` requires `SEARCH_FORM_DEFINITION_RULE`
- `"forms"` defines a collection of forms
  - `"Books and journals"` as title of a form
    - `"Has title"` is a simple input field without any constraints
    - `"Publication type"` is a input field with additional attributes

<pre>
{
    "type": "SEARCH_FORM_DEFINITION_RULE",
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

| Attributes | Values | Description |
|--------------|-----------------|-----------------------------------|
| autocomplete | true, false | whether the field should add an autocomplete function or not |
| tooltip | text or msg key | shows a tooltip with either a text or retrieves information from a message key |
| placeholder | text | shown instead of the property name |
| required | true, false | whether the field input is required before submitting or not |
| type | HTML5 | preselect a specific type field |

`default_form` can define a default form that is displayed when no other form was preselected.

<pre>
{
    "type": "SEARCH_FORM_DEFINITION_RULE",
    "default_form": "Books and journals",
    ...
}
</pre>

### Term parser

The `term_parser` prefix can be used to shorten the input cycle and summarize frequent properties so that a user can write:
 - `(in:foobar || phrase:foo bar) lang:fr` instead of
 - `<q>[[in:foobar]] || [[phrase:foo bar]]</q><q>[[Language code::fr]] OR [[Document language::fr]] OR [[File attachment.Content language::fr]] OR [[Has interlanguage link.Page content language::fr]]</q>`

<pre>
{
    "type": "SEARCH_FORM_DEFINITION_RULE",
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

### Namespaces

- ` "namespaces"`
  - `default_hide` hides the namespace box by default on the extended profile form
  - `"hide"` identify namespaces that should be hidden from appearing in any SMW related form
  - `"preselect"` assign a pre-selection of namespaces to a specific form
    - `"Books and journals"` specific form the pre-selection should be enacted

<pre>
{
    "type": "SEARCH_FORM_DEFINITION_RULE",
    "namespaces": {
        "default_hide": true,
        "hide": [
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


### Descriptions

Describes a form and is shown at the top of the form fields to inform users about the intent of
the form.

<pre>
{
    "descriptions": {
        "Books and journals": "Short description to be shown on top of a selected form"
    }
}
</pre>

## Technical notes

### Search engine

Classes that provide an interface to support MW's `SearchEngine` by transforming a search term into a SMW equivalent expression of query elements.

<pre>
SMW\MediaWiki\Search
┃
┠━ QueryBuilder
┠━ Search           # Implements the `SearchEngine`
┠━ SearchResult     # Individual result representation
┕━ SearchResultSet  # Contains a set of results return from the `QueryEngine`
</pre>

### Search profile and form builder

Classes that provide an additional search form to support structured searches in `Special:Search` with the help of the [`SpecialSearchProfileForm`](https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfileForm) hook.

<pre>
SMW\MediaWiki\Search
┃     ┃
┃     ┕━ Form               # Classes to generate a HTML from a JSON definition
┃
┕━ SearchProfileForm        # Interface to the `SpecialSearchProfileForm` hook
</pre>
