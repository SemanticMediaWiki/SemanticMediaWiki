# Semantic MediaWiki 2.3.1

Released on January 4th, 2016.

## Bug fixes

* #1248 Fixed misplaced replacement of `_` in the `ImportValueParser`
* #1252 Added [`$smwgEnabledInTextAnnotationParserStrictMode`](https://www.semantic-mediawiki.org/wiki/Help:$smwgEnabledInTextAnnotationParserStrictMode) allowing to reenable (by disabling strict mode which by default is enabled) multi-property assignments in `[[ :: ]]`
* #1256 Added creation of object ID's that are not yet available in `EmbeddedQueryDependencyLinksStore`
* #1268 Fixed 1.26/1.27 API/RawMode MediaWiki output changes
* #1255 Fixed output regression (T121761) in connection with `#ask` and generated template HTML output
* #1321 Added [`$smwgSparqlRepositoryConnectorForcedHttpVersion`](https://semantic-mediawiki.org/wiki/Help:$smwgSparqlRepositoryConnectorForcedHttpVersion) setting to set a specific HTTP version in case of a observed cURL issue (#1306)
