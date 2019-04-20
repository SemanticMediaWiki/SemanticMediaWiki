### `src/SQLStore/TableBuilder`

- `TableSchemaManager` handles all table definitions used by Semantic MediaWiki in a RDBMS agnostic way
- `Installer` takes the `TableSchemaManager` (holds the table definitions), `TableBuilder` (creates/updates/removes an individual table), and `TableBuildExaminer` (runs some pre/post examination checks after tables have been added or removed) to perform the setup or removal of database tables used by Semantic MediaWiki
- Contains all classes that implement RDBMS specific execution commands and auxiliary classes to hold table definitions

### `src/SQLStore/TableBuilder/Examiner`

- Contains individual classes used by the `TableBuildExaminer`

## Notes

To change the database schema for Semantic MediaWiki related tables have a look at the `TableSchemaManager` that contains relevant settings and configurations (please refer to the [database.schema.md](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/database.schema.md) document when working on database specific changes).

Currently three different RDBMS provider are supported including MySQL/MariaDB, PostgreSQL, and SQLite. If a different provider is required then adding a new class with RDBMS specific commands is the first step together with registering this class with the `TableBuilder` factory method. Besides adding a new class, it is __absolute__ necessary that entire test suite passes before an additional provider can be added to SMW core.

### Related hooks

- `SMW::SQLStore::Installer::BeforeCreateTablesComplete`
- `SMW::SQLStore::Installer::AfterCreateTablesComplete`
- `SMW::SQLStore::Installer::AfterDropTablesComplete`

## See also

- [`hook.sqlstore.installer.beforecreatetablescomplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.sqlstore.installer.beforecreatetablescomplete.md) contains an example on how to modify table definitions (e.g. adding additional indices)
