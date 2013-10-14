<?php

namespace SMW\Test;

use SMW\SemanticDataSerializer;
use SMW\DataValueFactory;
use SMw\SemanticData;
use SMW\DIWikiPage;
use SMW\Subobject;

/**
 * Tests for the SemanticDataSerializer class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SemanticDataSerializer
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SemanticDataSerializerTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SemanticDataSerializer';
	}

	/**
	 * Helper method that returns a SemanticDataSerializer object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return SemanticDataSerializer
	 */
	private function newInstance() {
		return new SemanticDataSerializer();
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testSerializeOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = $this->newInstance();
		$instance->serialize( 'Foo' );

	}

	/**
	 * @since 1.9
	 */
	public function testUnserializeInvalidVersionOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = $this->newInstance();
		$instance->unserialize( array( 'version' => 'Foo' ) );

	}

	/**
	 * @since 1.9
	 */
	public function testUnserializeInvalidSubjectDataItemException() {

		$this->setExpectedException( '\SMW\DataItemException' );

		$instance = $this->newInstance();
		$instance->unserialize( array( 'subject' => '--#Foo' ) );

	}

	/**
	 * @since 1.9
	 */
	public function testUnserializeMissingSubjectOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = $this->newInstance();
		$instance->unserialize( array() );

	}

	/**
	 * @dataProvider semanticDataProvider
	 *
	 * @since 1.9
	 */
	public function testSerializerUnserializerRountrip( $data ) {

		$instance   = $this->newInstance();
		$serialized = $instance->serialize( $data );

		$this->assertEquals(
			$serialized,
			$instance->serialize( $instance->unserialize( $serialized ) ),
			'Asserts that the intial serialized container is equal to a container after a roundtrip'
		);


		$this->assertEquals(
			$data->getHash(),
			$instance->unserialize( $serialized )->getHash(),
			'Asserts that the hash of the orginal SemanticData container equals that of the serialized-un-serialized container'
		);
	}

	/**
	 * @dataProvider typeChangeSemanticDataProvider
	 *
	 * @since 1.9
	 */
	public function testForcedTypeErrorDuringRountrip( $data, $type ) {

		$instance   = $this->newInstance();
		$serialized = $instance->serialize( $data );

		// Injects a different type to cause an error (this would normally
		// happen when a property definition is changed such as page -> text
		// etc.)
		$reflector = $this->newReflector();
		$property  = $reflector->getProperty( 'dataItemTypeIdCache' );
		$property->setAccessible( true );
		$property->setValue( $instance, array( $type => 2 ) );

		$unserialized = $instance->unserialize( $serialized );

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$unserialized,
			'Asserts the instance'
		);

		$this->assertNotEmpty(
			$unserialized->getErrors(),
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
		$foo->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );
		$provider[] = array( $foo );

		// #2 Single + single subobject entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = array( $foo );

		// #3 Multiple entries
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );
		$foo->addDataValue( DataValueFactory::newPropertyValue( 'Has queez', 'Xeey' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Fuz' ) );

		$subobject->setSemanticData( 'Bar' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Fuz' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

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
		$foo->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$provider[] = array( $foo, 'Has_fooQuex' );

		// #1 Single subobject entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has fomQuex', 'Bam' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = array( $foo, 'Has_fomQuex' );

		// #2 Combined
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );
		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = array( $foo, 'Has_fomQuex' );

		return $provider;
	}

}
