This file contains details about [Semantic MediaWiki's API][smwapi] for external use with a description
of available interfaces. For more details on MediaWiki's WebAPI, it is recommended to read this [website][mwapi].

## api.php?action=ask
The `Ask API` allows you to do ask queries against Semantic MediaWiki using the MediaWiki API and get results back serialized in one of the formats it supports.

The ask module supports one parameter, query, which takes the same string you'd feed into an `#ask` parser function, but urlencoded.

> api.php?action=ask&query=[[Modification date::%2B]]|%3FModification date|sort%3DModification date|order%3Ddesc&format=jsonfm

## api.php?action=askargs
The `Askargs API` module aims to take arguments in un-serialized form, so with as little ask-specific syntax as possible. It supports 3 arguments:

* `conditions`: The query conditions, ie the requirements for a subject to be included
* `printouts`: The query printeouts, ie the properties to show per subject
* `parameters`: The query parameters, ie all non-condition and non-printeout arguments

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

## api.php?action=smwinfo
An interface to access statistical information about the properties, values etc..

> api.php?action=smwinfo&format=json&info=proppagecount|propcount

The following parameters are available and may be concatenate using the "|" character.
* `proppagecount`: The total number of properties registered with a page.
* `declaredpropcount`: The total number of properties with a datatype assigned.
* `usedpropcount`: The total number of properties with at least one values assigned.
* `propcount`: The total number of property values assinged.
* `errorcount`: The total number of property values invalidly assinged.
* `querycount`: The total number of queries.
* `querysize`: The total size of all queries.
* `formatcount`: The query formats and their respective total usage count.
* `subobjectcount`: The total number of subobjects.
* `conceptcount`: The total number of concepts.

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

## api.php?action=browsebysubject
An interface to browse facts (the equivalent of `Special:Browse`) of a subject (wikipage) including special properties and subobjects.

> api.php?action=browsebysubject&subject=Main%20Page
> api.php?action=browsebysubject&subject=Main%20Page&subobject=_QUERYa0856d9fbd9e495af0963ecc75fcef14

#### Output serialization
```php
{
	"query": {
		"subject": "Main_Page#0##",
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

## api.php?action=browsebyproperty
An interface to browse properties (the equivalent of `Special:Properties`).

> api.php?action=browsebyproperty
> api.php?action=browsebyproperty&limit=100&format=json&property=name

#### Output serialization

```php
{
    "xmlns:Foaf": "http://xmlns.com/foaf/0.1/",
    "query": {
        "Foaf:name": {
            "label": "Foaf:name",
            "isUserDefined": true,
            "usageCount": 2
        },
        "Has_common_name": {
            "label": "Has common name",
            "isUserDefined": true,
            "usageCount": 1
        }
    },
    "version": 0.1,
    "meta": {
        "limit": 100,
        "count": 2,
        "isCached": true
    }
}
```

[mwapi]: https://www.mediawiki.org/wiki/API:Main_page "API:Main_page"
[smwapi]: https://www.semantic-mediawiki.org/wiki/Help:API "Help:API"
