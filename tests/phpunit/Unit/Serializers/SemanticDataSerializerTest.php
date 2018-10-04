<?php

namespace SMW\Tests\Serializers;

use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Serializers\SemanticDataSerializer;
use SMW\Subobject;
use SMW\Tests\Utils\UtilityFactory;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Serializers\SemanticDataSerializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataSerializerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataValueFactory;
	private $semanticDataFactory;

	public function testCanConstructor() {

		$this->assertInstanceOf(
			'\SMW\Serializers\SemanticDataSerializer',
			new SemanticDataSerializer()
		);
	}

	public function testInvalidSerializerObjectThrowsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = new SemanticDataSerializer();
		$instance->serialize( 'Foo' );
	}

	/**
	 * @dataProvider semanticDataProvider
	 */
	public function testSerializerDeserializerRountrip( $data ) {

		$instance = new SemanticDataSerializer();

		$this->assertInternalType(
			'array',
			$instance->serialize( $data )
		);
	}

	public function semanticDataProvider() {

		// Is a dataprovider therefore can't use the setUp
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$title = Title::newFromText( 'Foo' );

		#0 Empty container
		$foo = $this->semanticDataFactory->setSubject( DIWikiPage::newFromTitle( $title ) )->newEmptySemanticData();
		$provider[] = [ $foo ];

		#1 Single entry
		$foo = $this->semanticDataFactory->setSubject( DIWikiPage::newFromTitle( $title ) )->newEmptySemanticData();
		$foo->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has fooQuex', 'Bar' ) );
		$provider[] = [ $foo ];

		// #2 Single + single subobject entry
		$foo = $this->semanticDataFactory->setSubject( DIWikiPage::newFromTitle( $title ) )->newEmptySemanticData();
		$foo->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has subobjects', 'Bam' ) );

		$foo->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$provider[] = [ $foo ];

		#3 Multiple entries
		$foo = $this->semanticDataFactory->setSubject( DIWikiPage::newFromTitle( $title ) )->newEmptySemanticData();
		$foo->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has fooQuex', 'Bar' ) );
		$foo->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has queez', 'Xeey' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has subobjects', 'Bam' ) );
		$subobject->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has fooQuex', 'Fuz' ) );

		$subobject->setSemanticData( 'Bar' );
		$subobject->addDataValue( $this->dataValueFactory->newDataValueByText( 'Has fooQuex', 'Fuz' ) );

		$foo->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$provider[] = [ $foo ];

		return $provider;
	}

}
