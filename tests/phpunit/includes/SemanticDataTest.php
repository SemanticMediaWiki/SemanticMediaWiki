<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\SemanticData;
use SMW\Subobject;
use SMWDITime as DITime;
use Title;

/**
 * @covers \SMW\SemanticData
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $semanticDataValidator;
	private $dataValueFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->addConfiguration( 'smwgCreateProtectionRight', false );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testConstructor() {

		$instance = new SemanticData( DIWikiPage::newFromText( __METHOD__ ) );

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

		$instance = new SemanticData( DIWikiPage::newFromText( __METHOD__ ) );

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

		$instance = new SemanticData( DIWikiPage::newFromText( __METHOD__ ) );

		$instance->addPropertyValue(
			'addPropertyValue',
			DIWikiPage::doUnserialize( 'Foo#0##' )
		);

		$key = Localizer::getInstance()->getNamespaceTextById( SMW_NS_PROPERTY ) . ':' . 'addPropertyValue';

		$expected = [
			'propertyCount'  => 1,
			'propertyLabels' => [ $key ],
			'propertyValues' => [ 'Foo' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance
		);
	}

	public function testGetHash() {

		$instance = new SemanticData( DIWikiPage::newFromText( __METHOD__ ) );

		$instance->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' )
		);

		$subobject = $this->newSubobject( $instance->getSubject()->getTitle() );

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
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
		);

		$instance->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Bar', 'Foo' )
		);

		$instanceToCheck = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$instanceToCheck->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Bar', 'Foo' )
		);

		$instanceToCheck->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
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
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
		);

		$subobject->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Bar', 'Foo' )
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
			$this->dataValueFactory->newDataValueByText( 'Bar', 'Foo' )
		);

		$subobject->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
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
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
		);

		$secondHash = $instance->getHash();

		$this->assertNotEquals(
			$firstHash,
			$secondHash
		);

		$subobject = new Subobject( Title::newFromText( 'Foo' ) );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
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

		$this->setExpectedException( '\SMW\Exception\SubSemanticDataException' );

		$instance->addSubSemanticData(
			new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( 'addSubSemanticData' ) ) )
		);
	}

	public function testDifferentSubSemanticDataSubjectThrowsException() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$this->setExpectedException( '\SMW\Exception\SubSemanticDataException' );
		$instance->addSubobject( $this->newSubobject( Title::newFromText( 'addSubSemanticData' ) ) );
	}

	public function testImportDataFromForDifferentSubjectThrowsException() {

		$instance = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$this->setExpectedException( '\SMW\Exception\SemanticDataImportException' );

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

		$instance->setOption( SemanticData::OPT_LAST_MODIFIED, 1001 );

		$this->assertEquals(
			1001,
			$instance->getOption( SemanticData::OPT_LAST_MODIFIED )
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
			$instance->getOption( SemanticData::OPT_LAST_MODIFIED )
		);
	}

	public function testVisibility() {

		$title = Title::newFromText( __METHOD__ );
		$instance = new SemanticData( DIWikiPage::newFromTitle( $title ) );

		$instance->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( 'Has fooQuex', 'Bar' )
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

	public function testRemoveProperty() {

		$property = new DIProperty( 'Foo' );
		$instance = new SemanticData( DIWikiPage::newFromText( __METHOD__ ) );

		$instance->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Bar', NS_MAIN, '', 'Foobar' )
		);

		$this->assertTrue(
			$instance->hasProperty( $property )
		);

		$instance->removeProperty( $property );

		$this->assertFalse(
			$instance->hasProperty( $property )
		);
	}

	public function testGetPropertyValuesToReturnAnUnmappedArray() {

		$property = new DIProperty( 'Foo' );
		$instance = new SemanticData( DIWikiPage::newFromText( __METHOD__ ) );

		$instance->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Bar', NS_MAIN )
		);

		$this->assertArrayHasKey(
			0,
			$instance->getPropertyValues( $property )
		);
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

	public function testExtensionData() {

		$instance = new SemanticData(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$instance->setExtensionData( 'Foo', 42 );

		$this->assertEquals(
			42,
			$instance->getExtensionData( 'Foo' )
		);

		$callback = function() { return 42; };

		$instance->setExtensionData( 'Bar', $callback );

		$this->assertEquals(
			$callback,
			$instance->getExtensionData( 'Bar' )
		);
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
		ApplicationFactory::clear();

		$provider = [];

		$title = Title::newFromText( __METHOD__ );
		$subobject = $this->newSubobject( $title, __METHOD__, '999' );

		// #0
		$provider[] = [
			$title,
			new DIProperty( '_MDAT'),
			DITime::newFromTimestamp( 1272508903 )
		];

		// #1
		$provider[] = [
			$title,
			$subobject->getProperty(),
			$subobject->getContainer()
		];

		return $provider;
	}

	/**
	 * @return array
	 */
	public function dataValueDataProvider() {
		ApplicationFactory::clear();

		$provider = [];

		// #0 Single DataValue is added
		$provider[] = [
			[
				DataValueFactory::getInstance()->newDataValueByText( 'Foo', 'Bar' ),
			],
			[
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			]
		];

		// #1 Equal Datavalues will only result in one added object
		$provider[] = [
			[
				DataValueFactory::getInstance()->newDataValueByText( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newDataValueByText( 'Foo', 'Bar' ),
			],
			[
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			]
		];

		// #2 Two different DataValue objects
		$provider[] = [
			[
				DataValueFactory::getInstance()->newDataValueByText( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newDataValueByText( 'Lila', 'Lula' ),
			],
			[
				'error'         => 0,
				'propertyCount' => 2,
				'propertyLabels' => [ 'Foo', 'Lila' ],
				'propertyValues' => [ 'Bar', 'Lula' ]
			]
		];

		// #3 Error (Inverse)
		$provider[] = [
			[
				DataValueFactory::getInstance()->newDataValueByText( '-Foo', 'Bar' ),
			],
			[
				'error'         => 1,
				'propertyCount' => 0,
			]
		];

		// #4 One valid DataValue + an error object
		$provider[] = [
			[
				DataValueFactory::getInstance()->newDataValueByText( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newDataValueByText( '-Foo', 'bar' ),
			],
			[
				'error'         => 1,
				'propertyCount' => 1,
				'propertyLabels' => [ 'Foo' ],
				'propertyValues' => [ 'Bar' ]
			]
		];


		// #5 Error (Predefined)
		$provider[] = [
			[
				DataValueFactory::getInstance()->newDataValueByText( '_Foo', 'Bar' ),
			],
			[
				'error'         => 1,
				'propertyCount' => 0,
			]
		];

		// #6 Error (Known predefined property)
		$provider[] = [
			[
				DataValueFactory::getInstance()->newDataValueByText( 'Modification date', 'Bar' ),
			],
			[
				'error'         => 1,
				'propertyCount' => 0,
			]
		];

		return $provider;
	}

	private function newSubobject( Title $title, $property = 'Quuy', $value = 'Xeer' ) {

		$subobject = new Subobject( $title );
		$subobject->setEmptyContainerForId( 'Foo' );

		$subobject->addDataValue(
			DataValueFactory::getInstance()->newDataValueByText( $property, $value )
		);

		return $subobject;
	}

}
