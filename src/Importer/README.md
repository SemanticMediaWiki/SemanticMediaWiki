The objective of the `Importer` is to provide a simple mechanism for deploying
data structures and support information during the installation (setup) process.

## JSON format

The following `JSON` format has been selected to provide the technical means as
well as being easy to understand and extendable by end-users.

### Field definitions

* `description` short description about the purpose of the import (used in the auto summary)
* `page` the name of a page without a namespace prefix
* `namespace` literal constant of the namespace (e.g. NS_MAIN, SMW_NS_PROPERTY ... ) the content is to be imported
* `contents` contains either the raw text or as option specifies a
  * `importFrom` link to a file from where the raw text content is being fetched
* `options`
  * `canReplace` to indicate whether content is being allowed to be replaced during
  an import or not

The `smwgImportReqVersion` stipulates the required version with files only matching
the version to be allowed to be imported.

### Example

```
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
```

## Import process

During the setup process, the `Installer` will run the `ContentsImporter` and inform
about the process similar to:

```
Import of vocabulary.json ...
   ... replacing MediaWiki:Smw import foaf contents ...
   ... skipping Property:Foaf:knows, already exists ...

Import processing completed.
```
Unless indicated otherwise, content (a.k.a. pages) that pre-exists are going to be skipped by default.

## Technical notes

Services are listed in `ImporterServices.php` with the `SMW::SQLStore::Installer::AfterCreateTablesComplete` hook
to provide the execution event during the setup.

* `ContentsImporter` is responsible for importing content provided by the `ImportContentsIterator`
  * `ImportContentsIterator` provides access to individual `ImportContents` instances
  * `PageCreator`
* `JsonImportContentsIterator` implements the `ImportContentsIterator` interface
  * `JsonImportContentsFileDirReader` provides contents of all recursively fetched files from the `smwgImportFileDir`
  setting that meet the requirements and interprets the mentioned `JSON` definition to return instances of `ImportContents`

It is possible to implement a different format definition (CSV, XML etc.) by
providing a different `ImportContentsIterator` to the `ContentsImporter`.
