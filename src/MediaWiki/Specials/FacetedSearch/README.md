# Faceted Search

- An initial search condition (or restriction) is required to be able to start using `Special:FacetedSearch`
- If the search condition field is emptied, the user will be brought back to the initial `Special:FacetedSearch` screen
- Changing search conditions (in the search field) while actively browsing facets will reset all filters in order to recompute data required by the new conditional restriction
- A default profile is obligatory, other profiles can be added using the `FACETEDSEARCH_PROFILE_SCHEMA`

## Linking to the special page

Users can link to the special page using different patterns including:

- `Special:FacetedSearch/{{FULLPAGENAME}}` will only be useful for category, property, or concept pages
- `Special:FacetedSearch/Category/__category_name__`
- `Special:FacetedSearch/__property__/__value__`

The special page can also be embedded into a "normal" wiki page following `{{Special:FacetedSearch/Category:National Park Service}}`, yet any interaction with a filter will redirect the user to the `Special:FacetedSearch`.

## Managing profiles

Individual profiles can be created and maintained using the [`FACETEDSEARCH_PROFILE_SCHEMA`][FACETEDSEARCH_PROFILE_SCHEMA] schema. The `default`  profile is deployed with Semantic MediaWiki, yet, this profile __cannot__ (#4642) be altered hence creating your own profile with the same schema type is recommended to maintain particular preferences.

Further more, each logged-in user can add a profile preference (located within the Semantic MediaWiki preference section) and will be selected as default when using `Special:FacetedSearch`.

## Working with facets and filters

### Property and category hierarchy tree

The `hierarchy_tree` option is provided for the `category_filter` and `property_filter` to generate a tree like structure of categories (or properties) and related subentities to help visualize their dependencies.

![image](https://user-images.githubusercontent.com/1245473/76559789-26d3f400-64e3-11ea-92f4-4bfaca508925.png)

### Defining range groups

Working with discrete values may not always be possible due to the sheer amount of available values where filtering them individually could be unproductive for the overall browsing experience which is why the `range_group` filtering approach is provided. It allows to define group of values and make them accessible as range selection instead of displaying discrete values.

To use that feature, the `range_group_filter_preference` must be enabled for the selected profile to signal to the filtering component to find and create a possible `range_group` definitions for a selected property which then will replace individual values with the listed range groups.

A `range_group` a defined as part of a `PROPERTY_PROFILE_SCHEMA` assigned to a property.

![image](https://user-images.githubusercontent.com/1245473/76538693-0c8a1e00-64c3-11ea-8a9b-b5866b1bb27b.png)

### Filter condition

The `default` filter condition for an individual facet (or filter card) is OR (aka. the result should contain ...), yet it is possible to modify the behaviour by setting the `condition_field` as part of a profile to modify the conditional preference for an applied filter.

#### Disjunction (OR)

Should contain the conditional filter(s).

![image](https://user-images.githubusercontent.com/1245473/76436549-b4d4af80-63fb-11ea-8c41-feb963ed914b.png)

#### Conjunction (AND)

Must contain the conditional filter(s).

![image](https://user-images.githubusercontent.com/1245473/76436564-b9996380-63fb-11ea-93e8-3cc05243656d.png)

#### Negation (NOT)
Must not contain the conditional filter(s).

![image](https://user-images.githubusercontent.com/1245473/76436571-be5e1780-63fb-11ea-9185-68123830d0d9.png)

## Technical notes

<pre>
/src/MediaWiki/Specials (SMW\MediaWiki\Specials)
│	│
│	└─ SpecialFacetedSearch
│		│
/src/MediaWiki/Specials/FacetedSearch (SMW\MediaWiki\Specials\FacetedSearch)
│	├─ ExploreListBuilder		# Builds exploration list
│	├─ ExtraFieldBuilder
│	├─ FacetBuilder		# Creates individual filter cards
│	├─ OptionsBuilder		# Helper to create form and option fields
│	├─ HtmlBuilder			# Creates the entire HTML output
│	├─ ParametersProcessor		# Creates parameters from an request
│	├─ Profile
│	├─ ResultFetcher
│	└─ FilterFactory
│		│
/src/MediaWiki/Specials/FacetedSearch/Filters
│	│
│	├─ PropertyFilter
│	├─ CategoryFilter
│	├─ ValueFilter
│	└─ ValueFilterFactory
│		│
/src/MediaWiki/Specials/FacetedSearch/Filters/ValueFilters
		│
		├─ CheckboxRangeGroupValueFilter
		├─ CheckboxValueFilter
		├─ ListValueFilter
		└─ RangeValueFilter
</pre>

<pre>
─ smw-factedsearch {{theme}}-theme
		│
		├─ smw-factedsearch-search
		│		│
		│		└─ smw-factedsearch-search-form
		│
		├─ smw-factedsearch-extra-search
		│		│
		│		└─ smw-factedsearch-extra-search-fields
		│
		└─ smw-factedsearch-container
				│
				├─ smw-factedsearch-sidebar
				│		│
				│		└─ smw-factedsearch-filter
				│			│
				│			└─ filter-cards
				│				│
				│				├─ filter-card
				│				└─ ...
				│
				└─ smw-factedsearch-content
						│
						├─ smw-factedsearch-debug
						├─ smw-factedsearch-result-options
						└─ smw-factedsearch-result-output
</pre>

[FACETEDSEARCH_PROFILE_SCHEMA]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Schema/docs/facetedsearch.profile.md