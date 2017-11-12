The objective of the `Importer` is to provide a simple mechanism for deploying
data structures and support information in a loose yet structerd form during the installation (setup) process.

## Import definitions

[`$smwgImportFileDirs`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDirs) defines the import directory from where content is to be imported.

Import defintions are defined using a `JSON` definition which provides the structural means and easily adaptable and extendable by end-users.

The import files are sorted and therefore sequentially processed based on the file name. In case where content relies on other content an approriate naming convention should be followed to ensure required definitions are imported first.

### Default definitions

The pre-deployed `vocabulary.json` is __not__ expected to be the __authority
source__ of content for a wiki and is the reason why the option `canReplace` is set `false` so that pre-existing content with the same name and namespace is not replaced.

### Custom definitions

It is possible for a user to define custom import definitions by extending [`$smwgImportFileDirs`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDirs)
with a custom location (directory) from where import definitions can be loaded.

```
$GLOBALS['smwgImportFileDirs'] += [
	'movie-actor' => __DIR__ . '/import/movie-actor'
];
```

### Definition fields

`JSON` schema and fields:

- `description` short description about the purpose of the import (used in the auto summary)
- `page` the name of a page without a namespace prefix
- `namespace` literal constant of the namespace of the content  (e.g. `NS_MAIN`, `SMW_NS_PROPERTY` ... )
- `contents` it contains either the raw text or a parameter
  - `importFrom` link to a file from where the raw text (contains a relative path to the `$smwgImportFileDirs`)
- `options`
  - `canReplace` to indicate whether content is being allowed to be replaced during
  an import or not

The [`$smwgImportReqVersion`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportReqVersion) stipulates
the required version and only an import file that matches that version is permitted to be imported.

### Examples

#### XML import

It is possible to use MediaWiki's XML format as import source when linked from
`importFrom` (any non MediaWiki XML format will be ignored).

The location for the `custom.xml` is relative to `$smwgImportFileDirs` path with the
`Importer` using an auto discovery to find matchable source files.

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

#### Template import

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
				"canReplace": false
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
				"canReplace": false
			}
		}
	],
	"meta": {
		"version": "1"
	}
}
</pre>

## Import process

During the setup process, the `Installer` will run the `ContentsImporter` and inform
about the process similar to:

<pre>
Import of vocabulary.json ...
   ... replacing MediaWiki:Smw import foaf contents ...
   ... skipping Property:Foaf:knows, already exists ...

Import processing completed.
</pre>

If not otherwise specified, content (a.k.a. pages) that pre-exists are going to be skipped by default.

## Technical notes

Services are defined in `ImporterServices.php` and the `SMW::SQLStore::Installer::AfterCreateTablesComplete` hook
provides the execution event during the setup.

<pre>
ImporterServiceFactory

Importer
	|- ContentIterator
	|- ContentCreator

ContentIterator
	|- JsonContentIterator
		|- JsonImportContentsFileDirReader
		|- ContentModeller

ContentCreator
	| - DispatchingContentCreator
		|- XmlContentCreator
		|- TextContentCreator
</pre>

- `ImporterServiceFactory` access to import services
- `Importer` is responsible for importing contents provided by a `ContentIterator`
- `ContentIterator` an interface to provide access to individual `ImportContents` instances
  - `JsonContentIterator` implements the `ContentIterator` interface
    - `JsonImportContentsFileDirReader` provides contents of all recursively fetched files from a location (e.g[`$smwgImportFileDirs`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDirs) setting ) that meets the requirements
    - `ContentModeller` interprets the `JSON` definition and returns a set of `ImportContents` instances
- `ContentCreator` an interface to specify different creation methods (e.g. text, XML etc.)
  - `DispatchingContentCreator` dispatches to the actual content creation instance based on `ImportContents::getContentType`
    - `XmlContentCreator` support the creation of MediaWiki XML specific content
    - `TextContentCreator` support for raw wikitext
