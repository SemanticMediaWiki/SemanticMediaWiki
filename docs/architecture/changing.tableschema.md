### `src/SQLStore`

- `TableSchemaManager` handles all table definitions used by Semantic MediaWiki in a RDBMS agnostic way
- `Installer` takes the `TableSchemaManager` (holding the table definitions), `TableBuilder` (creates/updates/removes an individual table), and `TableBuildExaminer` (carries out some pre/post examination after tables have been added or removed) to perform the setup or removal of database tables used by Semantic MediaWiki

### `src/SQLStore/TableBuilder`

- Contains all classes that implement RDBMS specific execution commands and auxiliary classes to hold table definitions

### `src/SQLStore/TableBuilder/Examiner`

- Contains individual classes used by the `TableBuildExaminer`

## Related hooks

- `SMW::SQLStore::Installer::BeforeCreateTablesComplete`
- `SMW::SQLStore::Installer::AfterCreateTablesComplete`
- `SMW::SQLStore::Installer::AfterDropTablesComplete`

## See also

- [`hook.sqlstore.installer.beforecreatetablescomplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/code-snippets/hook.sqlstore.installer.beforecreatetablescomplete.md) contains an example how to modify table definitions (e.g. adding additional indices)
