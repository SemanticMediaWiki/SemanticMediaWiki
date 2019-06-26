This document provides a short introduction into the database schema and table structure used by Semantic MediaWiki.

## Database schema

The database schema is defined using the `TableSchemaManager` class and together with the `TableBuilder` is forming the interface to define, create, and remove database and schema related information used by Semantic MediaWiki.

The SPO (subject, predicate, and object) pattern paradigm is reflected in how semantic relations are orginized in Semantic MediaWiki. Three table types are used to store information with table fields normally being identified by its related intent such as `s_` (subject), `p_` (predicate, property), and `o_` (object).

The three table types are:

- `Data table` a table that to stores information not necessarily following the SPO pattern (statistics, ids, dependencies etc.)
- `Property table` (Common) a table (identified by `smw_di_...`) that follows the SPO pattern
- `Property table` (Fixed) a table (identified by `smw_fpt_...`) that follows the SPO pattern but omits the `p_id` as it is a designated table to one particular property

A special type are temporary tables only used during a search request (which remains in-memory) and is dropped as soon as a query is resolved.

### Data tables

Data tables follow individual schema definitions to serve a specific purpose for either collecting or aggregating data.

- `smw_object_ids` contains the entity and object references and is holding the foreign key (`smw_id` field) for all other tables that use an ID reference.
- `smw_query_links` collects embedded query dependencies.
- `smw_prop_stats` a table that collects of property statistics.
- `smw_ft_search` if enabled, contains a collection of full-text indexable text components.

### Property tables (Common)

User defined properties with an assigned datatype are stored in tables with a predefined structure and relevant fields required by the [`DataItem`][dataitem] to represent its literal value.

<pre>
[
	DataItem::TYPE_NUMBER     => 'smw_di_number',
	DataItem::TYPE_BLOB       => 'smw_di_blob',
	DataItem::TYPE_BOOLEAN    => 'smw_di_bool',
	DataItem::TYPE_URI        => 'smw_di_uri',
	DataItem::TYPE_TIME       => 'smw_di_time',
	DataItem::TYPE_GEO        => 'smw_di_coords',
	DataItem::TYPE_CONTAINER  => 'smw_di_container',
	DataItem::TYPE_WIKIPAGE   => 'smw_di_wikipage',
	DataItem::TYPE_CONCEPT    => 'smw_conc'
]
</pre>

Available fields include:

- `s_id` subject ID reference (see `smw_id`)
- `p_id` property ID reference (see `smw_id`)
- `o_...` fields that identify object related values to adhere the s-p-o pattern

### Property tables (Fixed)

Fixed property table assignments are either defined by [`TypesRegistry.php`][typesregistry] (for predefined properties) or by the `$smwgFixedProperties` setting (for user-defined fixed properties).

Available fields include:

- `s_id` subject ID reference (see `smw_id`)
- `o_...` fields that identify object related values to adhere the s-p-o pattern

## See also

- Working with and [changing the table schema](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/changing.tableschema.md)

[typesregistry]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/TypesRegistry.php
[dataitem]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/datamodel.dataitem.md
