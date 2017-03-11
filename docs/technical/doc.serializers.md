This document contains information about Semantic MediaWiki serializers.

## Components

### Serializer and Deserializer
Interfaces provided by the [Serialization extension][serialization] which describes specific serialize/deserialze public methods.

### SerializerFactory
A factory class that assigns registered serializers to an object and identifies an unserializer based on the invoked array. A serialized record has a reference to the generator (serializer) class which will automatically be used during unserialization. Each record includes a version number to compare the data model used and enable a consistency check before an attempt to unserialize a record.

```php
$foo = new Foo( ... );
$serialized = SerializerFactory::serialize( $foo );
$unserialized = SerializerFactory::deserialize( $serialized );
```

### SemanticDataSerializer
Implements the Serializer interface for the SMW\SemanticData object.

#### Data model
```php
"subject": -> Subject serialization,
"data": [
	{
		"property": -> Property serialization,
		"dataitem": [
			{
				"type": -> DataItemType,
				"item": -> DataItem serialization
			}
		]
	}
]
"sobj": [
	{
		"subject": ...,
		"data": [
			{
				"property": ...,
				"dataitem": [
					{
						"type": ...,
						"item": ...
					}
				]
			},
	},
],
"serializer": -> Class of the generator and entry point for the un-serializer,
"version": -> Number to compare structural integrity between serialization and un-serialization
```
#### Example
For a page called "Foo" that contains <code>[[Has property::Bar]]</code>, <code>{{#subobject:|Has subobjects=Bam}}</code>, <code>{{#ask:[[Has subobjects::Bam]]}}</code>, the Serializer will output:

```php
"subject": "Foo#0#",
"data": [
	{
		"property": "Has_property",
		"dataitem": [
			{
				"type": 9,
				"item": "Bar#0#"
			}
		]
	},
	{
		"property": "_ASK",
		"dataitem": [
			{
				"type": 9,
				"item": "Foo#0##_QUERYc8606da8f325fc05aa8e8b958821c3b4"
			}
		]
	},
	...
	{
		"property": "_SOBJ",
		"dataitem": [
			{
				"type": 9,
				"item": "Foo#0##_fc4b104aabf80eb06429e946aa8f7070"
			}
		]
	}
],
"sobj": [
	{
		"subject": "Foo#0##_QUERYc8606da8f325fc05aa8e8b958821c3b4",
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
	},
	...
	{
		"subject": "Foo#0##_fc4b104aabf80eb06429e946aa8f7070",
		"data": [
			{
				"property": "Has_subobjects",
				"dataitem": [
					{
						"type": 9,
						"item": "Bam#0#"
					}
				]
			},
			{
				"property": "_SKEY",
				"dataitem": [
					{
						"type": 2,
						"item": "Foo"
					}
				]
			}
		]
	}
],
"serializer": "SMW\\Serializers\\SemanticDataSerializer",
"version": 0.1
```

### QueryResultSerializer
Implements the SerializerInterface for the SMWQueryResult object.

[serialization]: [https://github.com/wikimedia/mediawiki-extensions-Serialization]
