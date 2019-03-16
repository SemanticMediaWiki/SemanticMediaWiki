### `src/Property/Annotator`

- Add a new class that implements the [Annotator](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Property/Annotator.php) (alias `PropertyAnnotator`) interface via the `PropertyAnnotatorDecorator` base class
- Register the implemenation with the `AnnotatorFactory`
- Use either the [`NewRevisionFromEditComplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Hooks/NewRevisionFromEditComplete.php) or [`ParserAfterTidy`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Hooks/ParserAfterTidy.php) hook to invoke the `AnnotatorFactory` method created and annotate values as demonstrated by existing `PropertyAnnotator`

### See also

- [`AttachmentLinkPropertyAnnotator.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Property/Annotators/AttachmentLinkPropertyAnnotator.php)
- [`TranslationPropertyAnnotator.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Property/Annotators/TranslationPropertyAnnotator.php)