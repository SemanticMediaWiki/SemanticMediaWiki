### `src/SQLStore/TableBuilder`

- `TableSchemaManager` handles all table definitions used by Semantic MediaWiki in a RDBMS agnostic way
- `Installer` takes in the
  - `TableSchemaManager` (holds the table definitions),
  - `TableBuilder` (creates/updates/removes an individual table), and
  - `TableBuildExaminer` (runs some pre/post examination checks after tables have been added or removed) to perform the setup or removal of database tables used by Semantic MediaWiki
- `TableBuilder` implements RDBMS specific execution commands and auxiliary classes

### `src/SQLStore/TableBuilder/Examiner`

- Contains individual classes used by the `TableBuildExaminer`

## Notes

Changing the database schema for Semantic MediaWiki should be done using the `TableSchemaManager` (please refer to the [database.schema.md](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/database.schema.md) document when working on database specific changes).

Currently three different RDBMS provider are supported including MySQL/MariaDB, PostgreSQL, and SQLite, to add a different provider it is required to create a new class with RDBMS specific commands together with registering this class using the `TableBuilder::factory` method. Aside from adding a new class, it is paramount that the new class is tested and passes the test suite before any additional provider can be registered with SMW core.

### Related hooks

- [`SMW::SQLStore::Installer::BeforeCreateTablesComplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.beforecreatetablescomplete.md)
- [`SMW::SQLStore::Installer::AfterCreateTablesComplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.aftercreatetablescomplete.md)
- [`SMW::SQLStore::Installer::AfterDropTablesComplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.afterdroptablescomplete.md)

## See also

- [`hook.sqlstore.installer.beforecreatetablescomplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.sqlstore.installer.beforecreatetablescomplete.md) contains an example on how to modify table definitions (e.g. adding additional indices)
