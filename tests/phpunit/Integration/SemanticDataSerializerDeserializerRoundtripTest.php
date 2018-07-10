<?php

namespace SMW\Tests\Integration;

use ReflectionClass;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\Deserializers\SemanticDataDeserializer;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Serializers\SemanticDataSerializer;
use SMW\Subobject;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataSerializerDeserializerRoundtripTest extends \PHPUnit_Framework_TestCase {

	private function newSerializerInstance() {
		return new SemanticDataSerializer();
	}

	private function newDeserializerInstance() {
		return new SemanticDataDeserializer();
	}

	/**
	 * @dataProvider semanticDataProvider
	 */
	public function testSerializerDeserializerRountrip( $data ) {

		$serialized = $this->newSerializerInstance()->serialize( $data );

		$this->assertEquals(
			$serialized,
			$this->newSerializerInstance()->serialize( $this->newDeserializerInstance()->deserialize( $serialized ) )
		);


		$this->assertEquals(
			$data->getHash(),
			$this->newDeserializerInstance()->deserialize( $serialized )->getHash()
		);
	}

	/**
	 * @dataProvider incompleteSubobjectDataProvider
	 */
	public function testSerializerDeserializerWithIncompleteSubobjectData( $data ) {

		$serialized = $this->newSerializerInstance()->serialize( $data );

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$this->newDeserializerInstance()->deserialize( $serialized )
		);
	}

	/**
	 * @dataProvider typeChangeSemanticDataProvider
	 */
	public function testForcedTypeErrorDuringRountrip( $data, $type ) {

		$serialized   = $this->newSerializerInstance()->serialize( $data );
		$deserializer = $this->newDeserializerInstance();

		// Injects a different type to cause an error (this would normally
		// happen when a property definition is changed such as page -> text
		// etc.)
		$reflector = new ReflectionClass( '\SMW\Deserializers\SemanticDataDeserializer' );
		$property  = $reflector->getProperty( 'dataItemTypeIdCache' );
		$property->setAccessible( true );
		$property->setValue( $deserializer, [ $type => 2 ] );

		$deserialized = $deserializer->deserialize( $serialized );

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$deserialized
		);

		$this->assertNotEmpty(
			$deserialized->getErrors()
		);
	}

	public function semanticDataProvider() {

		ApplicationFactory::clear();

		$provider = [];
		$title = \Title::newFromText( __METHOD__ );

		// #0 Empty container
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$provider[] = [ $foo ];

		// #1 Single entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' ) );
		$provider[] = [ $foo ];

		// #2 Single + single subobject entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has subobjects', 'Bam' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = [ $foo ];

		// #3 Multiple entries
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has queez', 'Xeey' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has subobjects', 'Bam' ) );
		$subobject->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Fuz' ) );

		$subobject->setSemanticData( 'Bar' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Fuz' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = [ $foo ];

		return $provider;
	}

	/**
	 * @return array
	 */
	public function incompleteSubobjectDataProvider() {

		$provider = [];

		$title = \Title::newFromText( __METHOD__ );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );

		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' ) );
		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getSemanticData()->getSubject() );

		$provider[] = [ $foo ];

		return $provider;
	}

	/**
	 * @return array
	 */
	public function typeChangeSemanticDataProvider() {

		$provider = [];
		$title = \Title::newFromText( __METHOD__ );

		// #0 Single entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' ) );

		$provider[] = [ $foo, 'Has_fooQuex' ];

		// #1 Single subobject entry
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fomQuex', 'Bam' ) );

		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = [ $foo, 'Has_fomQuex' ];

		// #2 Combined
		$foo = new SemanticData( DIWikiPage::newFromTitle( $title ) );
		$foo->addDataValue( DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' ) );
		$foo->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$provider[] = [ $foo, 'Has_fomQuex' ];

		return $provider;
	}

}
