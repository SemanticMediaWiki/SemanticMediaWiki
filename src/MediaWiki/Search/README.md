[SMWSearch](https://www.semantic-mediawiki.org/wiki/Help:SMWSearch) is the `SearchEngine` interface to provide classes and functions to integrate Semantic MediaWiki with `Special:Search`.

It adds support for using `#ask` queries in the `Special:Search` context and provides an extended search profile where user defined forms can empower users to find and match entities using property and value input fields.

## Extended search profile

In cases where the systems detects forms maintained using the `SEARCH_FORM_SCHEMA`, an extended profile will be visible on the `Special:Search` page allowing users to match and search subjects with help of Semantic MediaWiki.

### Example

![image](https://user-images.githubusercontent.com/1245473/43684698-7748fd76-9894-11e8-971f-3125892dc9ed.png)

## Technical notes

Classes that provide an interface to support MW's `SearchEngine` by transforming a search term into a SMW equivalent expression of query elements.

<pre>
SMW\MediaWiki\Search
   │
   ├─ QueryBuilder
   ├─ SearchEngineFactory
   ├─ ExtendedSearchEngine     # Implements the `SearchEngine`
   │     └─ ExtendedSearch
   ├─ SearchResult             # Individual result representation
   └─ SearchResultSet          # Contains a set of results return from the `QueryEngine`
</pre>

Classes that provide an additional search form to support structured searches in `Special:Search` with the help of the [`SpecialSearchProfileForm`](https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchProfileForm) hook.

<pre>
SMW\MediaWiki\Search\ProfileForm
   │
   └─ ProfileForm      # Interface to the `SpecialSearchProfileForm` hook
         └─ Forms      # Classes to generate a HTML from a JSON definition
</pre>

## See also

- [`search.form.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/search.form.md) describes the `SEARCH_FORM_SCHEMA` schema to define forms to be used in the extended `Special:Search` profile
