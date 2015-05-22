<?php

namespace SMW\Tests;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\MwDBaseUnitTestCase;

use SMW\StoreFactory;
use SMW\Localizer;
use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Subobject;

use SMWDITime as DITime;

use Title;

/**
 * @covers \SMW\SemanticData
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $dataValueFactory;

	protected function setUp() {
		parent::setUp();

		// DIProperty::findPropertyTypeID is called during the test
		// which itself will access the store and to avoid unnecessary
		// DB reads inject a mock
		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->dataValueFactory = DataValueFactory::getInstance();

		StoreFactory::setDefaultStoreForUnitTest( $store );
	}

	protected function tearDown() {
		StoreFactory::clear();
	}

	public function testConstructor() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$instance
		);

		$this->assertInstanceOf(
			'SMWSemanticData',
			$instance
		);
	}

	public function testGetPropertyValues() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$this->assertInstanceOf(
			'SMW\DIWikiPage',
			$instance->getSubject()
		);

		$this->assertEmpty(
			$instance->getPropertyValues( new DIProperty( 'Foo', true ) )
		);

		$this->assertEmpty(
			$instance->getPropertyValues( new DIProperty( 'Foo' ) )
		);
	}

	public function testAddPropertyValue() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$instance->addPropertyValue(
			'addPropertyValue',
			DIWikiPage::doUnserialize( 'Foo#0#' )
		);

		$key = Localizer::getInstance()->getNamespaceTextById( SMW_NS_PROPERTY ) . ':' . 'addPropertyValue';

		$expected = array(
			'propertyCount'  => 1,
			'propertyLabels' => array( $key ),
			'propertyValues' => array( 'Foo' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance
		);
	}

	public function testGetHash() {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$subobject = $this->newSubobject( $title );

		$instance->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$this->assertInternalType(
			'string',
			$instance->getHash()
		);
	}

	public function testPropertyOrderDoesNotInfluenceHash() {

		$instance = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$instance->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
		);

		$instance->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Bar', 'Foo' )
		);

		$instanceToCheck = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$instanceToCheck->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Bar', 'Foo' )
		);

		$instanceToCheck->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
		);

		$this->assertEquals(
			$instance->getHash(),
			$instanceToCheck->getHash()
		);
	}

	public function testSubSemanticPropertyOrderDoesNotInfluenceHash() {

		$subobject = new Subobject( Title::newFromText( 'Foo' ) );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
		);

		$subobject->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Bar', 'Foo' )
		);

		$instance = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$instance->addSubobject(
			$subobject
		);

		$subobject = new Subobject( Title::newFromText( 'Foo' ) );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Bar', 'Foo' )
		);

		$subobject->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
		);

		$instanceToCheck = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$instanceToCheck->addSubobject(
			$subobject
		);

		$this->assertEquals(
			$instance->getHash(),
			$instanceToCheck->getHash()
		);
	}

	public function testThatChangingDataDoesEnforceDifferentHash() {

		$instance = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$firstHash = $instance->getHash();

		$instance->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
		);

		$secondHash = $instance->getHash();

		$this->assertNotEquals(
			$firstHash,
			$secondHash
		);

		$subobject = new Subobject( Title::newFromText( 'Foo' ) );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
		);

		$instance->addSubSemanticData(
			$subobject->getSemanticData()
		);

		$thirdHash = $instance->getHash();

		$this->assertNotEquals(
			$secondHash,
			$thirdHash
		);

		// Remove the data added in the third step and expect
		// the hash from the second
		$instance->removeSubSemanticData(
			$subobject->getSemanticData()
		);

		$this->assertEquals(
			$secondHash,
			$instance->getHash()
		);
	}

	public function testGetSubSemanticData() {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		// Adds only a subobject reference to the container
		$subobject = $this->newSubobject( $title );

		$instance->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getSemanticData()->getSubject()
		);

		$this->assertNotInstanceOf(
			'SMWContainerSemanticData',
			$instance->getSubSemanticData()
		);

		// Adds a complete container
		$instance->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		foreach ( $instance->getSubSemanticData() as $subSemanticData ) {

			$this->assertInstanceOf(
				'SMWContainerSemanticData',
				$subSemanticData
			);
		}
	}

	public function testAddAndRemoveSubSemanticData() {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		// Adds only a subobject reference to the container
		$subobject = $this->newSubobject( $title );

		$instance->addSubobject( $subobject );

		$this->assertInternalType(
			'array',
			$instance->getSubSemanticData()
		);

		foreach ( $instance->getSubSemanticData() as $subSemanticData ) {

			$this->assertInstanceOf(
				'SMWContainerSemanticData',
				$subSemanticData
			);

			$this->assertEquals(
				$subSemanticData,
				$subobject->getSemanticData()
			);
		}

		$instance->removeSubSemanticData( $subobject->getSemanticData() );

		$this->assertNotInstanceOf(
			'SMWContainerSemanticData',
			$instance->getSubSemanticData()
		);
	}

	public function testAddSubSemanticDataWithOutSubobjectNameThrowsException() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$this->setExpectedException( 'MWException' );

		$instance->addSubSemanticData(
			new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( 'addSubSemanticData' ) ) )
		);
	}

	public function testDifferentSubSemanticDataSubjectThrowsException() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$this->setExpectedException( 'MWException' );
		$instance->addSubobject( $this->newSubobject( Title::newFromText( 'addSubSemanticData' ) ) );
	}

	public function testImportDataFromForDifferentSubjectThrowsException() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$this->setExpectedException( 'MWException' );

		$instance->importDataFrom(
			new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( 'importDataFrom' ) ) )
		);
	}

	public function testHasAndFindSubSemanticData() {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$subobject = $this->newSubobject( $title );
		$subobjectName = $subobject->getSemanticData()->getSubject()->getSubobjectName();

		$this->assertFalse(	$instance->hasSubSemanticData() );
		$this->assertEmpty(	$instance->findSubSemanticData( $subobjectName ));

		// Adds only a subobject reference to the container
		$instance->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getSemanticData()->getSubject()
		);

		$this->assertFalse( $instance->hasSubSemanticData( $subobjectName ) );
		$this->assertEmpty( $instance->findSubSemanticData( $subobjectName ) );

		$instance->addSubSemanticData( $subobject->getSemanticData() );

		$this->assertTrue( $instance->hasSubSemanticData( $subobjectName ) );
		$this->assertNotEmpty($instance->findSubSemanticData( $subobjectName ) );

		$this->assertInstanceOf(
			'SMWContainerSemanticData',
			$instance->findSubSemanticData( $subobjectName )
		);
	}

	public function testSubSemanticDataForNonStringSubobjectName() {

		$instance = new SemanticData(
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) )
		);

		$this->assertFalse(
			$instance->hasSubSemanticData( new \stdClass )
		);

		$this->assertEmpty(
			$instance->findSubSemanticData( new \stdClass )
		);
	}

	public function testSetLastModified() {

		$instance = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$instance->setLastModified( 1001 );

		$this->assertEquals(
			1001,
			$instance->getLastModified()
		);
	}

	public function testGetLastModifiedForEmptyModificationDate() {

		$instance = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$this->assertNull(
			$instance->getLastModified()
		);
	}

	public function testGetLastModifiedFromModificationDate() {

		$instance = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$instance->addPropertyObjectValue(
			new DIProperty( '_MDAT' ),
			DITime::newFromTimestamp( 1272508903 )
		);

		$this->assertEquals(
			1272508903,
			$instance->getLastModified()
		);
	}

	public function testVisibility() {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$this->assertTrue(
			$instance->hasVisibleProperties()
		);

		$instance->addSubobject(
			$this->newSubobject( $title )
		);

		$this->assertTrue(
			$instance->hasVisibleSpecialProperties()
		);
	}

	/**
	 * @dataProvider removePropertyObjectProvider
	 */
	public function testRemovePropertyObjectValue( $title, $property, $dataItem ) {

		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$instance->addPropertyObjectValue( $property, $dataItem );
		$this->assertFalse( $instance->isEmpty() );

		$instance->removePropertyObjectValue( $property, $dataItem );
		$this->assertTrue( $instance->isEmpty() );
	}

	public function testClear() {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$instance->addPropertyObjectValue(
			new DIProperty( '_MDAT' ),
			DITime::newFromTimestamp( 1272508903 )
		);

		$this->assertFalse( $instance->isEmpty() );

		$instance->clear();
		$this->assertTrue( $instance->isEmpty() );
	}

	/**
	 * @dataProvider dataValueDataProvider
	 */
	public function testAddDataValues( $dataValues, $expected ) {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		foreach ( $dataValues as $dataValue ) {
			$instance->addDataValue( $dataValue );
		}

		if ( $expected['error'] > 0 ) {
			return $this->assertCount( $expected['error'], $instance->getErrors() );
		}

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance
		);
	}

	/**
	 * @return array
	 */
	public function removePropertyObjectProvider() {

		$provider = array();

		$title = Title::newFromText( __METHOD__ );
		$subobject = $this->newSubobject( $title, __METHOD__, '999' );

		// #0
		$provider[] = array(
			$title,
			new DIProperty( '_MDAT'),
			DITime::newFromTimestamp( 1272508903 )
		);

		// #1
		$provider[] = array(
			$title,
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function dataValueDataProvider() {

		$provider = array();

		// #0 Single DataValue is added
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			)
		);

		// #1 Equal Datavalues will only result in one added object
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			)
		);

		// #2 Two different DataValue objects
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newPropertyValue( 'Lila', 'Lula' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 2,
				'propertyLabels' => array( 'Foo', 'Lila' ),
				'propertyValues' => array( 'Bar', 'Lula' )
			)
		);

		// #3 Error (Inverse)
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( '-Foo', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		// #4 One valid DataValue + an error object
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newPropertyValue( '-Foo', 'bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 1,
				'propertyLabels' => array( 'Foo' ),
				'propertyValues' => array( 'Bar' )
			)
		);


		// #5 Error (Predefined)
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( '_Foo', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		// #6 Error (Known predefined property)
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Modification date', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		return $provider;
	}

	private function newSubobject( Title $title, $property = 'Quuy', $value = 'Xeer' ) {

		$subobject = new Subobject( $title );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( $property, $value )
		);

		return $subobject;
	}

}
