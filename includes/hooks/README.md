[Hooks][hooks] are so-called event handlers that allow custom code to be executed. Semantic MediaWiki (SMW) uses several of those hooks to enable specific process logic to be integrated with MediaWiki. SMW currently uses the following hooks:

#### ArticlePurge
ArticlePurge is executed during a manual purge action of an article and depending on available settings is being used to track a page refresh.

#### BeforePageDisplay
BeforePageDisplay allows last minute changes to the output page and is being used to render a ExportRDF link into each individual article.

#### InternalParseBeforeLinks
InternalParseBeforeLinks is used to process and expand text content, and in case of SMW it is used to identify and resolve the property annotation syntax ([[link::syntax]]), returning a modified content component and storing annotations within the ParserOutput object.

#### LinksUpdateConstructed
LinksUpdateConstructed is called at the end of LinksUpdate and is being used to initiate a store update for data that were held by the ParserOutput object.

#### ParserAfterTidy
ParserAfterTidy is used to re-introduce content, update base annotations (e.g. special properties, categories etc.) and in case of a manual article purge initiates a store update (LinksUpdateConstructed wouldn't work because it acts only on link changes and therefore would not trigger a LinksUpdateConstructed event).

[hooks]: https://www.mediawiki.org/wiki/Hooks "Manual:Hooks"