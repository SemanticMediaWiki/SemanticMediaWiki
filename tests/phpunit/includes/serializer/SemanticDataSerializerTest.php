<?php

namespace SMW\Test;

use SMW\Serializers\SemanticDataSerializer;

use SMW\DataValueFactory;
use SMw\SemanticData;
use SMW\DIWikiPage;
use SMW\Subobject;

/**
 * @covers \SMW\Serializers\SemanticDataSerializer
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataSerializerTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return 'SMW\Serializers\SemanticDataSerializer';
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
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newSerializerInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testSerializerOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = $this->newSerializerInstance();
		$instance->serialize( 'Foo' );

	}

	/**
	 * @dataProvider semanticDataProvider
	 *
	 * @since 1.9
	 */
	public function testSerializerDeserializerRountrip( $data ) {

		$serialized = $this->newSerializerInstance()->serialize( $data );

		$this->assertInternalType(
			'array',
			$serialized,
			'Asserts that serialize() returns an array'
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

}
