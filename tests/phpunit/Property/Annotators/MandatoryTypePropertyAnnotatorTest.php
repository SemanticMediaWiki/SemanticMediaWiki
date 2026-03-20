<?php

namespace SMW\Tests\Property\Annotators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValueFactory;
use SMW\Property\Annotators\MandatoryTypePropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Property\Annotators\MandatoryTypePropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class MandatoryTypePropertyAnnotatorTest extends TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$this->assertInstanceOf(
			MandatoryTypePropertyAnnotator::class,
			$instance
		);
	}

	public function testNoImportForNoProperty() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();
	}

	public function testNoImportForPredefinedProperty() {
		$subject = WikiPage::newFromText( 'Modification date', SMW_NS_PROPERTY );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();
	}

	public function testValidImportTypeReferenceToSetType() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new Blob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Text' ),
			new Property( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = [
			'properties' => [ new Property( '_TYPE' ), new Property( '_IMPO' ) ],
			'propertyValues' => [ 'Text', 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testValidImportTypeReferenceToOverrideUserType() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new Blob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Page' ),
			new Property( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$typeValue = DataValueFactory::getInstance()->newDataValueByItem(
			new Uri( 'http', 'semantic-mediawiki.org/swivt/1.0', '', '_txt' ),
			new Property( '_TYPE' )
		);

		$semanticData->addDataValue( $typeValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		// Check before
		$expected = [
			'properties' => [ new Property( '_TYPE' ), new Property( '_IMPO' ) ],
			'propertyValues' => [ 'Text', 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);

		$instance->addAnnotation();

		// Check after
		$expected = [
			'properties' => [ new Property( '_TYPE' ), new Property( '_IMPO' ) ],
			'propertyValues' => [ 'Page', 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testInvalidImportTypeReferenceDoesNotSetAnyType() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new Blob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type-Text' ),
			new Property( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = [
			'properties' => [ new Property( '_IMPO' ) ],
			'propertyValues' => [ 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testBogusImportTypeDoesNotSetAnyType() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$importValue = DataValueFactory::getInstance()->newDataValueByItem(
			new Blob( 'foo' . ' ' . 'bar' . ' ' . 'buz' . ' ' . 'Type:Bogus' ),
			new Property( '_IMPO' )
		);

		$semanticData->addDataValue( $importValue );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->addAnnotation();

		$expected = [
			'properties' => [ new Property( '_IMPO' ) ],
			'propertyValues' => [ 'foo:bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testEnforcedMandatoryTypeForSubproperty() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY )
		);

		$parent = new WikiPage( 'Foo', SMW_NS_PROPERTY );

		$subpro = DataValueFactory::getInstance()->newDataValueByItem(
			$parent,
			new Property( '_SUBP' )
		);

		$semanticData->addDataValue( $subpro );

		$instance = new MandatoryTypePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$instance->setSubpropertyParentTypeInheritance( true );
		$instance->addAnnotation();

		$this->assertEquals(
			$parent,
			$semanticData->getOption( MandatoryTypePropertyAnnotator::ENFORCED_PARENTTYPE_INHERITANCE )
		);
	}

}
