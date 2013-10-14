This document contains information about Semantic MediaWiki serializers.

## Components

### Serializer and Deserializer
Interfaces provided by the [Serialization extension][serialization] which describes specific serialize/deserialze public methods.

### SerializerFactory
A factory class that assigns registered serializers to an object and identifies an unserializer based on the invoked array. A serialized record has a reference to the generator (serializer) class which will automatically be used during unserialization. Each record includes a version number to compare the data model used and enable a consistency check before an attempt to unserialize a record.

```php
$foo = new Foo( ... );
$serialized = SerializerFactory::serialize( $foo );
$unserialized = SerializerFactory::unserialize( $serialized );
```

### SemanticDataSerializer
Implements the Serializer interface for the SMW\SemanticData object.

#### Data model
```php
[subject] -> Subject serialization
[data] -> array container
	[property] -> Property serialization
	[dataitem] -> DataItem serialization
	...
[sobj] -> array container
	[subject] -> Subobject subject serialization
	[data] -> array container
		[property] -> Property serialization
		[dataitem] -> DataItem serialization
		...
[serializer] -> Class of the generator and entry point for the un-serializer
[version] -> Number to compare structural integrity between serialization and un-serialization
```
#### Example
For a page called "Foo" that contains <code>[[Has property::Bar]]</code>, <code>{{#subobject:|Has subobjects=Bam}}</code>, <code>{{#ask:Has subobjects::Bam}}</code>, the Serializer will output:

```php
[subject] => Foo#0#
[data] => Array (
	[0] => Array (
		[property] => Has_property
		[dataitem] => Array (
			[0] => Array (
				[type] => 9
				[item] => Bar#0#
			)
		)
	)
	[1] => Array (
		[property] => _ASK
		[dataitem] => Array (
			[0] => Array (
				[type] => 9
				[item] => Foo#0##_QUERYc8606da8f325fc05aa8e8b958821c3b4
				[sobj] => _QUERYc8606da8f325fc05aa8e8b958821c3b4
			)
		)
	)
	[2] => Array (
		[property] => _MDAT
		[dataitem] => Array (
			[0] => Array (
				[type] => 6
				[item] => 1/2013/10/10/14/55/40
			)
		)
	)
	[3] => Array (
		[property] => _SKEY
		[dataitem] => Array (
			[0] => Array (
				[type] => 2
				[item] => Foo
			)
		)
	)
	[4] => Array (
		[property] => _SOBJ
		[dataitem] => Array (
			[0] => Array (
				[type] => 9
				[item] => Foo#0##_fc4b104aabf80eb06429e946aa8f7070
				[sobj] => _fc4b104aabf80eb06429e946aa8f7070
			)
		)
	)
)
[sobj] => Array (
	[0] => Array (
		[subject] => Foo#0##_fc4b104aabf80eb06429e946aa8f7070
		[data] => Array (
			[0] => Array (
				[property] => Has_subobjects
				[dataitem] => Array (
					[0] => Array (
						[type] => 9
						[item] => Bam#0#
					)
				)
			)
		)
	)
	...
)
[serializer] => SMW\Serializers\SemanticDataSerializer
[version] => 0.1
```

### QueryResultSerializer
N/A (see \SMW\DISerializer)

[serialization]: [https://github.com/wikimedia/mediawiki-extensions-Serialization]