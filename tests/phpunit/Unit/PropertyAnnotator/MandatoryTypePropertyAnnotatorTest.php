<?php

namespace SMW\Tests\PropertyAnnotator;

use SMW\Tests\Utils\UtilityFactory;

use SMW\PropertyAnnotator\MandatoryTypePropertyAnnotator;
use SMW\PropertyAnnotator\NullPropertyAnnotator;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\DataValueFactory;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;

/**
 * @covers \SMW\PropertyAnnotator\MandatoryTypePropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MandatoryTypePropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\MandatoryTypePropertyAnnotator',
			$instance
		);
	}

	public function testNoImportForNoProperty() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();
	}

	public function testNoImportForPredefinedProperty() {

		$subject = DIWikiPage::newFromText( 'Modification date', SMW_NS_PROPERTY );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();
	}

	public function testValidImportTypeReferenceToSetType() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataItemValue(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Text' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = array(
			'properties' => array( new DIProperty( '_TYPE' ), new DIProperty( '_IMPO' ) ),
			'propertyValues' => array( 'Text', 'foo:bar' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testValidImportTypeReferenceToOverrideUserType() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataItemValue(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Page' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$typeValue = DataValueFactory::getInstance()->newDataItemValue(
			new DIUri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', '_txt' ),
			new DIProperty( '_TYPE' )
		);

		$semanticData->addDataValue( $typeValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		// Check before
		$expected = array(
			'properties' => array( new DIProperty( '_TYPE' ), new DIProperty( '_IMPO' ) ),
			'propertyValues' => array( 'Text', 'foo:bar' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);

		$instance->addAnnotation();

		// Check after
		$expected = array(
			'properties' => array( new DIProperty( '_TYPE' ), new DIProperty( '_IMPO' ) ),
			'propertyValues' => array( 'Page', 'foo:bar' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testInvalidImportTypeReferenceDoesNotSetAnyType() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataItemValue(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type-Text' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = array(
			'properties' => array( new DIProperty( '_IMPO' ) ),
			'propertyValues' => array( 'foo:bar' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testBogusImportTypeDoesNotSetAnyType() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataItemValue(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Bogus' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = array(
			'properties' => array( new DIProperty( '_IMPO' ) ),
			'propertyValues' => array( 'foo:bar' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

}
