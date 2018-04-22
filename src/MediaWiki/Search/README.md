## SMWSearch

[SMWSearch](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch) contains classes and functions that integrates SMW with `Special:Search`.

### Search engine

Classes that provide an interface to support MW's `SearchEngine` by transforming a search terms into a SMW equivalent expression of query elements.

- `Search`
- `SearchResult`
- `SearchResultSet`

### Search profile

Classes that provide an additional search form to support structured searches in `Special:Search` with the help of the [`SpecialSearchProfileForm`](https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfileForm) hook.

- `SearchProfileForm`
- `QueryBuilder`

Form specific classes to generate a HTML from a JSON definition that is hosted in `Rule:Search-profile-form-definition`.

- `Form\FormsBuilder`
- `Form\FormsFactory`
- `Form\OpenForm`
- `Form\CustomForm`
- `Form\NamespaceForm`
- `Form\SortForm`
- `Form\Field`

#### JSON

Defining forms

```
{
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
                }
            }
        ]
    }
}
```

| Attributes | Values | Description |
|--------------|-----------------|-----------------------------------|
| autocomplete | true, false | whether the field should add a autocomplete function or not |
| tooltip | text or msg key | shows a tooltip with either a text or retrieves information from a message key |
| placeholder | text | shown instead of the property name |
| required | true, false | whether the field input is required before submitting |
| type | HTML5 | preselect a specific type field |type | HTML5 | preselect a specific type field |

Enforcing a namespace selection for a specific form

```
    "namespaces": {
        "Books and journals": [
            "NS_CUSTOM_BOOKS"
        ]
    },
```

Describing a form

```
    "descriptions": {
        "Books and journals": "Short description to be shown on top of a selected form"
    }
```