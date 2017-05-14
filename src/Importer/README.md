The objective of the `Importer` is to provide a simple mechanism for deploying
data structures and support information during the installation (setup) process.

The pre-deployed `vocabulary.json` contains a minimal use case on how to faciliate 
the `Importer` and provides a simple introduction on the import of [vocabularies](https://www.semantic-mediawiki.org/wiki/Help:Import_vocabulary) using Semantic MediaWiki.

## Import definitions

The content to be imported in a definition file is sequential and works from top to bottom
therefore content that relies on other content requires to follow this rule and
it extends to definition files as well where file `a-content.json` comes before
`b-content.json`.

By default, the pre-deployed `vocabulary.json` is __not__ expected to be the __authority
source__ of content for a wiki and is the reason why option `canReplace` is set `false`
so that pre-existing content is not replaced.

It is possible for a user to define additional import definitions that contain template definitions,
additional property definitions by pointing [`$smwgImportFileDir`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDir)
to another location (directory) from where import definitions are fetched.

It is further possible to use MediaWiki's XML format as import source when linked from
`importFrom` (any non MediaWiki XML format will be ignored).

### JSON fields

The following `JSON` schema has been selected to provide structural means as
well as being easy to understand and extendable by end-users.

* `description` short description about the purpose of the import (used in the auto summary)
* `page` the name of a page without a namespace prefix
* `namespace` literal constant of the namespace of the content  (e.g. `NS_MAIN`, `SMW_NS_PROPERTY` ... )
* `contents` it contains either the raw text or a parameter
  * `importFrom` link to a file from where the raw text (contains a relative path to the `$smwgImportFileDir`)
* `options`
  * `canReplace` to indicate whether content is being allowed to be replaced during
  an import or not

The [`$smwgImportReqVersion`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportReqVersion) stipulates
the required version and only an import file that matches that version is permitted to be imported.

### Examples

#### Modified default vocabulary import

<pre>
{
	"description": "Semantic MediaWiki default vocabulary import",
	"import": [
		{
			"page": "Smw import foaf",
			"namespace": "NS_MEDIAWIKI",
			"contents": {
				"importFrom": "foaf.txt"
			},
			"options": {
				"canReplace": true
			}
		},
		{
			"page": "Foaf:knows",
			"namespace": "SMW_NS_PROPERTY",
			"contents": "[[Imported from::foaf:knows]] ... ",
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

#### Custom import definitions

The location for the `custom.xml` is relative to `$smwgImportFileDir` where the
importer will use an auto discovery to find all source files.

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

<pre>
{
	"description": "Template import",
	"import": [
		{
			"description" : "Template definition contains ...",
			"page": "SomeTemplate",
			"namespace": "NS_TEMPLATE",
			"contents": {
				"importFrom": "/templ/template-1.txt"
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
Importer
	|- ContentIterator
	|- ContentCreator

ContentIterator
	|- JsonContentIterator
		|- JsonImportContentsFileDirReader

ContentCreator
	| - DispatchingContentCreator
		|- XmlContentCreator
			|- ImporterServiceFactory
		|- TextContentCreator
			|- PageCreator
			|- Database
</pre>

* `Importer` is responsible for importing contents provided by a `ContentIterator`
* `ContentIterator` an interface to provide access to individual `ImportContents` instances
* `ContentCreator` an interface to specify different creation methods (e.g. text, XML etc.)
* `JsonContentIterator` implements the `ContentIterator` interface
* `JsonImportContentsFileDirReader` provides contents of all recursively fetched files from the [`$smwgImportFileDir`](https://www.semantic-mediawiki.org/wiki/Help:$smwgImportFileDir)
  setting that meet the requirements and interprets the described `JSON` definition to return a set of `ImportContents` instances
* `ImporterServiceFactory` access to import services
* `DispatchingContentCreator` dispatches to the actual content creation instance based on `ImportContents::getContentType`
* `XmlContentCreator` support the creation of MediaWiki XML specific content
* `TextContentCreator` support for raw wikitext
