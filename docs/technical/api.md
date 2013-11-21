This file contains details about Semantic MediaWiki's API for external use with a description that is reflecting the current master branch (SMW 1.9). For more details on "how to use" MediaWiki's WebAPI, it is recommended to read this [website][api].

## SMW\Api\Ask
The Ask API allows you to do ask queries against SMW using the MediaWiki API and get results back serialized in one of the formats it supports.

The ask module supports one parameter, query, which takes the same string you'd feed into an #ask tag, but urlencoded.

> api.php?action=ask&query=[[Modification date::%2B]]|%3FModification date|sort%3DModification date|order%3Ddesc&format=jsonfm

### SMW\Api\AskArgs
The Askargs module aims to take arguments in un-serialized form, so with as little ask-specific syntax as possible. It supports 3 arguments:

* "conditions": The query conditions, ie the requirements for a subject to be included
* "printouts": The query printeouts, ie the properties to show per subject
* "parameters": The query parameters, ie all non-condition and non-printeout arguments

> api.php?action=askargs&conditions=Modification date::%2B&printouts=Modification date&parameters=|sort%3DModification date|order%3Ddesc&format=jsonfm

#### Output serialization
```php
{
	"query-continue-offset": 50,
	"query": {
		"printrequests": [
			{
				"label": "",
				"typeid": "_wpg",
				"mode": 2,
				"format": false
			},
			{
				"label": "Modification date",
				"typeid": "_dat",
				"mode": 1,
				"format": ""
			}
		],
		"results": {
			"Main Page": {
				"printouts": {
					"Modification date": [
						"1381456128"
					]
				},
				"fulltext": "Main Page",
				"fullurl": "http:\/\/localhost:8080\/mw\/index.php\/Main_Page",
				"namespace": 0,
				"exists": true
			},
			...
		},
		"meta": {
			"hash": "a9abdb34024fa8735f6b044305a48619",
			"count": 50,
			"offset": 0
		}
	}
}
```

## SMW\Api\Info
An interface to access statistical information about the properties, values etc..

> api.php?action=smwinfo&format=json&info=proppagecount|propcount

The following parameters are available and can be concatenate using the "|" character.
* proppagecount
* propcount
* querycount
* usedpropcount
* declaredpropcount
* conceptcount
* querysize
* subobjectcount
* formatcount

#### Output serialization

```php
{
	"info": {
		"proppagecount": 40,
		"formatcount": {
			"table": 14,
			"list": 3,
			"broadtable": 1
		}
	}
}
```
The parameter "formatcount" will output an array of used formats together with its count information.

## SMW\Api\BrowseBySubject
An interface to browse facts of a subject (wikipage) including special properties and subobjects.

> api.php?action=browsebysubject&subject=Main%20Page

#### Output serialization
```php
{
	"query": {
		"subject": "Main_Page#0#",
		"data": [
			{
				"property": "Foo",
				"dataitem": [
					{
						"type": 2,
						"item": "Bar"
					}
				]
			}
			...
		],
		"sobj": [
			{
				"subject": "Main_Page#0##_QUERYa0856d9fbd9e495af0963ecc75fcef14",
				"data": [
					{
						"property": "_ASKDE",
						"dataitem": [
							{
								"type": 1,
								"item": "1"
							}
						]
					},
				...
				]
			...
			}
		],
		"serializer": "SMW\Serializers\SemanticDataSerializer",
		"version": 0.1
	}
}
```
The output is generated using the <code>SMW\SerializerFactory</code> which if necessary can also be used to un-serialize the data received from the Api. For details about the output format and how to use <code>SMW\SerializerFactory</code>, see <code>/docs/serializer.md</code>.

```php
$pai = new SMW\Api\BrowseBySubject( ... )
$result = $api->getResultData();
$semanticData = SerializerFactory::deserialize( $result['query'] );
```

[api]: https://www.mediawiki.org/wiki/Api "Manual:Api"