[Hooks][hooks] are so-called event handlers that allow costum code to be executed. Semantic MediaWiki (SMW) uses several of those hooks to enable specific process logic to be integrated with MediaWiki. SMW currently integrates the following hooks:

#### ArticlePurge
ArticlePurge executes during a pruge action of an article and depending on available settings is being used to track a manual refresh of a page.

#### BeforePageDisplay
BeforePageDisplay allows last minute changes to the output page and is being used to render a ExportRDF link into each individual article.

#### InternalParseBeforeLinks
InternalParseBeforeLinks is used to process and expand text content, and in case of SMW is used to indentify and resolve [[link::syntax]] property annotation syntax, returning a modified content component and storing property annotations within the ParserOutput object.

#### LinksUpdateConstructed
LinksUpdateConstructed is called at the end of LinksUpdate and is being used to iniate a store update for data that where stored in the ParserOutput object.

#### ParserAfterTidy
ParserAfterTidy is used to re-introduce content, update base annotations (e.g. special properties, categories etc.) and in case of a manual article purge initiates a store update (LinksUpdateConstructed wouldn't work because on link has been changed and therefore would not trigger a LinksUpdateConstructed event).

[hooks]: https://www.mediawiki.org/wiki/Hooks "Manual:Hooks"