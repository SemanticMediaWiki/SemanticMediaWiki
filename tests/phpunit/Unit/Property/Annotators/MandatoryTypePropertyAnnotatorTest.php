<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Property\Annotators\MandatoryTypePropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;

/**
 * @covers \SMW\Property\Annotators\MandatoryTypePropertyAnnotator
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
			'\SMW\Property\Annotators\MandatoryTypePropertyAnnotator',
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

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Text' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = [
			'properties' => [ new DIProperty( '_TYPE' ), new DIProperty( '_IMPO' ) ],
			'propertyValues' => [ 'Text', 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testValidImportTypeReferenceToOverrideUserType() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Page' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$typeValue = DataValueFactory::getInstance()->newDataValueByItem(
			new DIUri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', '_txt' ),
			new DIProperty( '_TYPE' )
		);

		$semanticData->addDataValue( $typeValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		// Check before
		$expected = [
			'properties' => [ new DIProperty( '_TYPE' ), new DIProperty( '_IMPO' ) ],
			'propertyValues' => [ 'Text', 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);

		$instance->addAnnotation();

		// Check after
		$expected = [
			'properties' => [ new DIProperty( '_TYPE' ), new DIProperty( '_IMPO' ) ],
			'propertyValues' => [ 'Page', 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testInvalidImportTypeReferenceDoesNotSetAnyType() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type-Text' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = [
			'properties' => [ new DIProperty( '_IMPO' ) ],
			'propertyValues' => [ 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testBogusImportTypeDoesNotSetAnyType() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new DIBlob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Bogus' ),
			new DIProperty( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = [
			'properties' => [ new DIProperty( '_IMPO' ) ],
			'propertyValues' => [ 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testEnforcedMandatoryTypeForSubproperty() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$parent = new DIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$subpro = DataValueFactory::getInstance()->newDataValueByItem(
			$parent,
			new DIProperty( '_SUBP' )
		);

		$semanticData->addDataValue( $subpro );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->setSubpropertyParentTypeInheritance( true );
		$instance->addAnnotation();

		$this->assertEquals(
			$parent,
			$semanticData->getOption( MandatoryTypePropertyAnnotator::ENFORCED_PARENTTYPE_INHERITANCE  )
		);
	}

}
