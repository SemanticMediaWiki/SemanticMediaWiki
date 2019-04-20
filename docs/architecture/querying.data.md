## Querying and displaying data

When resolving a query request (i.e. resolving the parser function `{{#ask ... }}` or `{{#show: ... }}`) the `QueryEngine` has two distinct tasks to accomplish:

- First, build a query condition in a corresponding language (SQL, ES-DSL, SPARQL) that is understood by the assigned QueryEngine where it returns a list of subjects that match the condition (similar to finding rows in a table that match a WHERE condition)
- Secondly, build a `QueryResult` object that contains the matched subjects and provide information on the requested printouts (equivalent of columns displayed in a table)

### `src/ParserFunctions`

- [`AskParserFunction.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ParserFunctions/AskParserFunction.php) defines the `#ask` parser function
- [`ShowParserFunction.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ParserFunctions/ShowParserFunction.php) defines the `#show` parser function

### `src/Query`

- [`Parser.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/Parser.php) interface to `src/Query/Parser` to create a `Description` and `Query` object from an `{{#ask: ...}}` string
- [`PrintRequest.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/PrintRequest.php)
- [`ResultPrinter.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/ResultPrinter.php) interface to concrete implementations found in `src/Query/ResultPrinters`

### `src/Query/Language`

Each condition elememt (e.g. `[[Has foo::bar]] || [[!Foo]]`) of a query is representated by a `Description` object allowing to express a query condition as as description [AST](https://en.wikipedia.org/wiki/Abstract_syntax_tree).

- [`Description.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/Language/Description.php)

### `src/Query/Result`

- [`QueryResult.php`] the instance represents the matched subjects of a query request (i.e. rows of a table)
- [`ResultArray.php`] represents a lazy-object to contain the printouts (see `PrintRequest`) for a particular subject (i.e. as columns for a particulr row)

### `src/Query/ResultPrinters`

A `ResultPrinter` is the user facing output formatter that takes a `QueryResult` and transform its representation into a specific format by extending:

- [`ResultPrinter.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/ResultPrinters/ResultPrinter.php) is an abstract base class to provide accessors and pre and postprocess the output after an individual printer returns the formatted result or
- [`FileExportPrinter.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/ResultPrinters/FileExportPrinter.php) is an abstract base class for file export result printers

## Examples

- [`boilerplate.resultprinter`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/boilerplate.resultprinter.md) starting point for writing a result printer
- [`boilerplate.fileexportprinter`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/boilerplate.fileexportprinter.md) starting point for writing a file result printer
- [`boilerplate.resultprinter.example`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/boilerplate.resultprinter.example.md) a complete example
- [`query.description.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/query.description.md) how to build a `Descripion` objects
- [`query.someproperty.of.type.number.md`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/query.someproperty.of.type.number.md) example on how to query a number