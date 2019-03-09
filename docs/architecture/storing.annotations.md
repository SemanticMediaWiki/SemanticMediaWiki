## Creating annotations and storing data

### `src/Parser`

- [`InTextAnnotationParser.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Parser/InTextAnnotationParser.php) is the main entrypoint for handling of `[[...::...]]` annotations

### `src/ParserFunctions`

- [`SetParserFunction.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ParserFunctions/SetParserFunction.php) defines the `#set` parser function
- [`SubobjectParserFunction.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ParserFunctions/SubobjectParserFunction.php) defines the `#subobject` parser function
- [`RecurringEventsParserFunction.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ParserFunctions/RecurringEventsParserFunction.php) defines the `#set_recurring_event` parser function

### `src`

- [`SemanticData.php`] is the storage and lookup representation for all semantic data assigned to a single subject
- [`ParserData.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/ParserData.php) prepares the data retrieved from the `ParserOutput` (where it is temporary stored during a user request and transferred from MediaWiki's `Parser`)
- [`DataUpdater.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/DataUpdater.php) prepares the data `SemanticData` injected by `ParserData` to make some final adjustments before posting it to `Store::updateData`
