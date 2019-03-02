The objective of the `Importer` is to provide a simple mechanism for deploying data structures and support information in a loose yet structured form during the installation (setup) process.

## Import definitions

[`$smwgImportFileDirs`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDirs) defines import directories from where content can be imported.

Import definitions are defined using a `JSON` format which provides the structural means and is considered easily extendable by end-users.

The import files are sorted and therefore sequentially processed based on the file name. In case where content relies on other content an appropriate naming convention should be followed to ensure required definitions are imported in the expected order.

Semantic MediaWiki deploys preselected import content which is defined in the "smw.vocab.json" file and includes:

* "Smw import skos"
* "Smw import owl"
* "Smw import foaf"
* "Foaf:knows"
* "Foaf:name" and
* "Foaf:homepage"

It should be noted that `smw.vocab.json` is __not__ expected to be the __authority source__ of content for a wiki and is the reason why the option `replaceable` is set to `false` so that pre-existing content that matches the same name and namespace is not replaced by the importer.

### Custom definitions

It is possible to define one or more custom import definitions using [`$smwgImportFileDirs`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDirs) with a custom location (directory) from where import definitions can be loaded.

<pre>
$GLOBALS['smwgImportFileDirs']['custom-vocab'] = __DIR__ . '/custom';
</pre>

### Fields

`JSON` schema and fields:

- `description` short description about the purpose of the import (used in the auto summary)
- `page` the name of a page without a namespace prefix
- `namespace` literal constant of the namespace of the content  (e.g. `NS_MAIN`, `SMW_NS_PROPERTY` ... )
- `contents` it contains either the raw text or a parameter
  - `importFrom` link to a file from where the raw text (contains a relative path to the `$smwgImportFileDirs`)
- `options`
  - `replaceable` to indicate whether content is being allowed to be replaced during an import or not and can take `true`, `false`, or `{ "LAST_EDITOR": "IS_IMPORTER" }` to support a replacement when the last editor is the same as the import creator (hereby provides a method to extend content as long as the source page wasn't altered by someone or something else).

The [`$smwgImportReqVersion`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportReqVersion) stipulates the required version for an import where only definitions that match that version are permitted to be imported.

### Examples

#### XML import

It is possible to use MediaWiki's XML format as import source when linked from the
`importFrom` field (any non MediaWiki XML format will be ignored).

The location for the mentioned `custom.xml` is relative to the selected `$smwgImportFileDirs` directory.

<pre>
{
	"description": "Custom import",
	"import": [
		{
			"description" : "Import of custom.xml that contains ...",
			"contents": {
				"importFrom": "/xml/custom.xml"
			}
		}
	],
	"meta": {
		"version": "1"
	}
}
</pre>

#### Text import

<pre>
{
	"description": "Template import",
	"import": [
		{
			"description" : "Template to ...",
			"page": "Template_1",
			"namespace": "NS_TEMPLATE",
			"contents": "<includeonly>{{{1}}}, {{{2}}}</includeonly>",
			"options": {
				"replaceable": false
			}
		},
		{
			"description" : "Template with ...",
			"page": "Template_2",
			"namespace": "NS_TEMPLATE",
			"contents": {
				"importFrom": "/templates/template-1.tmpl"
			},
			"options": {
				"replaceable": false
			}
		}
	],
	"meta": {
		"version": "1"
	}
}
</pre>

## Import process

During the setup process, the `Installer` will automatically run and inform
about the process which will output something similar to:

<pre>
Import of smw.vocab.json ...
   ... replacing MediaWiki:Smw import foaf contents ...
   ... skipping Property:Foaf:knows, already exists ...

Import processing completed.
</pre>

If not otherwise specified, content (a.k.a. pages) that pre-exists are going to be skipped by default.

## Technical notes

`SMW::SQLStore::Installer::AfterCreateTablesComplete` is the event to import content during the setup

- src
  - Importer
    - `ImporterServiceFactory` access to import services
    - `Importer` is responsible for importing contents provided by a `ContentIterator`
    - `ContentIterator` an interface to provide access to individual `ImportContents` instances
    - `JsonContentIterator` implements the `ContentIterator` interface
    - `JsonImportContentsFileDirReader` provides contents of all recursively fetched files from a location (e.g[`$smwgImportFileDirs`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDirs) setting ) that meets the requirements
    - `ContentModeller` interprets the `JSON` definition and returns a set of `ImportContents` instances
    - `ContentCreator` an interface to specify different creation methods (e.g. text, XML etc.)
    - ContentCreators
      - `DispatchingContentCreator` dispatches to the actual content creation instance based on `ImportContents::getContentType`
      - `XmlContentCreator` support the creation of MediaWiki XML specific content
      - `TextContentCreator` support for raw wikitext
