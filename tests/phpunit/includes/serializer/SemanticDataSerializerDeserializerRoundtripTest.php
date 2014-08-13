<?php

namespace SMW\Test;

use SMW\Deserializers\SemanticDataDeserializer;
use SMW\Serializers\SemanticDataSerializer;

use SMW\DataValueFactory;
use SMw\SemanticData;
use SMW\DIWikiPage;
use SMW\Subobject;

/**
 * @covers \SMW\Deserializers\SemanticDataDeserializer
 * @covers \SMW\Serializers\SemanticDataSerializer
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataSerializerDeserializerRoundtripTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * Helper method that returns a SemanticDataSerializer object
	 *
	 * @since 1.9
	 */
	private function newSerializerInstance() {
		return new SemanticDataSerializer();
	}

	/**
	 * Helper method that returns a SemanticDataDeserializer object
	 *
	 * @since 1.9
	 */
	private function newDeserializerInstance() {
		return new SemanticDataDeserializer();
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( '\SMW\Serializers\SemanticDataSerializer', $this->newSerializerInstance() );
		$this->assertInstanceOf( '\SMW\Deserializers\SemanticDataDeserializer', $this->newDeserializerInstance() );
	}

	/**
	 * @dataProvider semanticDataProvider
	 *
	 * @since 1.9
	 */
	public function testSerializerDeserializerRountrip( $data ) {

		$serialized = $this->newSerializerInstance()->serialize( $data );

		$this->assertEquals(
			$serialized,
			$this->newSerializerInstance()->serialize( $this->newDeserializerInstance()->deserialize( $serialized ) ),
			'Asserts that the intial serialized container is equal to a container after a roundtrip'
		);


		$this->assertEquals(
			$data->getHash(),
			$this->newDeserializerInstance()->deserialize( $serialized )->getHash(),
			'Asserts that the hash of the orginal SemanticData container equals that of the serialized-un-serialized container'
		);
	}

	/**
	 * @dataProvider incompleteSubobjectDataProvider
	 *
	 * @since 1.9.0.2
	 */
	public function testSerializerDeserializerWithIncompleteSubobjectData( $data ) {

		$serialized = $this->newSerializerInstance()->serialize( $data );

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$this->newDeserializerInstance()->deserialize( $serialized ),
			'Asserts that SemanticData instance is returned for an incomplete data set'
		);

	}

	/**
	 * @dataProvider typeChangeSemanticDataProvider
	 *
	 * @since 1.9
	 */
	public function testForcedTypeErrorDuringRountrip( $data, $type ) {

		$serialized   = $this->newSerializerInstance()->serialize( $data );
		$deserializer = $this->newDeserializerInstance();

		// Injects a different type to cause an error (this would normally
		// happen when a property definition is changed such as page -> text
		// etc.)
		$reflector = $this->newReflector( '\SMW\Deserializers\SemanticDataDeserializer' );
		$property  = $reflector->getProperty( 'dataItemTypeIdCache' );
		$property->setAccessible( true );
		$property->setValue( $deserializer, array( $type => 2 ) );

		$deserialized = $deserializer->deserialize( $serialized );

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$deserialized,
			'Asserts the instance'
		);

		$this->assertNotEmpty(
			$deserialized->getErrors(),
			'Asserts that getErrors() returns not empty'
		);

	}

	/**
	 * @return array
	 */
	public function semanticDataProvider() {

		$provider = array();
		$title = $this->newTitle( NS_MAIN, 'Foo' );

		// #0 Empty container
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$provider[] = array( $foo );

		// #1 Single entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );
		$provider[] = array( $foo );

		// #2 Single + single subobject entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has subobjects', 'Bam' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = array( $foo );

		// #3 Multiple entries
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has queez', 'Xeey' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has subobjects', 'Bam' ) );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Fuz' ) );

		$subobject->setSemanticData( 'Bar' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Fuz' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = array( $foo );

		return $provider;
	}

	/**
	 * @return array
	 */
	public function incompleteSubobjectDataProvider() {

		$provider = array();

		$title = $this->newTitle( NS_MAIN, 'Foo' );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );

		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );
		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getSemanticData()->getSubject() );

		$provider[] = array( $foo );

		return $provider;
	}

	/**
	 * @return array
	 */
	public function typeChangeSemanticDataProvider() {

		$provider = array();
		$title = $this->newTitle( NS_MAIN, 'Foo' );

		// #0 Single entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$provider[] = array( $foo, 'Has_fooQuex' );

		// #1 Single subobject entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fomQuex', 'Bam' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = array( $foo, 'Has_fomQuex' );

		// #2 Combined
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );
		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = array( $foo, 'Has_fomQuex' );

		return $provider;
	}

}
