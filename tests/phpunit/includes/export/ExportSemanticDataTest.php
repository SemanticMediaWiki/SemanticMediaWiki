<?php

namespace SMW\Tests\Export;

use SMW\Tests\Utils\SemanticDataFactory;
use SMW\Tests\Utils\Validators\ExportDataValidator;

use SMW\Tests\Utils\Fixtures\FixturesProvider;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;
use SMW\Subobject;

use SMWExporter as Exporter;
use SMWExpNsResource as ExpNsResource;
use SMWExpResource as ExpResource;

/**
 * @covers \SMWExporter
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ExportSemanticDataTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $dataValueFactory;
	private $exportDataValidator;
	private $fixturesProvider;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->exportDataValidator = new ExportDataValidator();

		$this->fixturesProvider = new FixturesProvider();
	}

	public function testExportRedirect() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$redirectProperty = new DIProperty( '_REDI' );
		$redirectTarget = new DIWikiPage( 'FooRedirectTarget', NS_MAIN, '' );

		$semanticData->addPropertyObjectValue(
			$redirectProperty,
			DIWikiPage::newFromTitle( $redirectTarget->getTitle(), '__red' )
		);

		$exportData = Exporter::makeExportData( $semanticData );

		$this->assertCount(
			1,
			$exportData->getValues( Exporter::getSpecialNsResource( 'swivt', 'redirectsTo' ) )
		);

		$this->assertCount(
			1,
			$exportData->getValues( Exporter::getSpecialNsResource(  'owl', 'sameAs' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			'FooRedirectTarget',
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki',
			$redirectTarget
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			Exporter::getSpecialNsResource( 'owl', 'sameAs' ),
			$exportData
		);
	}

	public function testExportPageWithNumericProperty() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$property = new DIProperty( '123' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( '345', NS_MAIN )
		);

		$exportData = Exporter::makeExportData( $semanticData );

		$expectedProperty = new ExpNsResource(
			Exporter::getInstance()->getEncodedPropertyNamespace() . '123',
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki',
			new DIWikiPage( '123', SMW_NS_PROPERTY )
		);

		$this->assertCount(
			1,
			$exportData->getValues( $expectedProperty )
		);

		$this->exportDataValidator->assertThatExportDataContainsProperty(
			$expectedProperty,
			$exportData
		);

		$expectedResourceElement = new ExpNsResource(
			'345',
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki',
			new DIWikiPage( '345', NS_MAIN )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			$expectedProperty,
			$exportData
		);
	}

	public function testExportPageWithNonNumericProperty() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$property = new DIProperty( 'A123' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( '345', NS_MAIN )
		);

		$exportData = Exporter::makeExportData( $semanticData );

		$expectedProperty = new ExpNsResource(
			'A123',
			Exporter::getNamespaceUri( 'property' ),
			'property',
			new DIWikiPage( 'A123', SMW_NS_PROPERTY )
		);

		$this->assertCount(
			1,
			$exportData->getValues( $expectedProperty )
		);

		$this->exportDataValidator->assertThatExportDataContainsProperty(
			$expectedProperty,
			$exportData
		);

		$expectedResource = new ExpNsResource(
			'345',
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki',
			new DIWikiPage( '345', NS_MAIN )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResource,
			$expectedProperty,
			$exportData
		);
	}

	public function testExportSubproperty() {

		$semanticData = $this->semanticDataFactory
			->setSubject( new DIWikiPage( 'SomeSubproperty', SMW_NS_PROPERTY ) )
			->newEmptySemanticData();

		$semanticData->addDataValue(
			$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_SUBP' ), 'SomeTopProperty' )
		);

		$exportData = Exporter::makeExportData( $semanticData );

		$this->assertCount(
			1,
			$exportData->getValues( Exporter::getSpecialNsResource( 'rdfs', 'subPropertyOf' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			'SomeTopProperty',
			Exporter::getNamespaceUri( 'property' ),
			'property',
			new DIWikiPage( 'SomeTopProperty', SMW_NS_PROPERTY )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			Exporter::getSpecialNsResource( 'rdfs', 'subPropertyOf' ),
			$exportData
		);
	}

	public function testExportCategory() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue(
			$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_INST' ), 'SomeCategory' )
		);

		$exportData = Exporter::makeExportData( $semanticData );

		$this->assertCount(
			2,
			$exportData->getValues( Exporter::getSpecialNsResource( 'rdf', 'type' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			Exporter::getEncodedPageName( new DIWikiPage( 'SomeCategory', NS_CATEGORY ) ),
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki',
			new DIWikiPage( 'SomeCategory', NS_CATEGORY )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			Exporter::getSpecialNsResource( 'rdf', 'type' ),
			$exportData
		);
	}

	public function testExportSubcategory() {

		$semanticData = $this->semanticDataFactory
			->setSubject( new DIWikiPage( 'SomeSubcategory', NS_CATEGORY ) )
			->newEmptySemanticData();

		$semanticData->addDataValue(
			$this->dataValueFactory->newPropertyObjectValue( new DIProperty( '_SUBC' ), 'SomeTopCategory' )
		);

		$exportData = Exporter::makeExportData( $semanticData );

		$this->assertCount(
			1,
			$exportData->getValues( Exporter::getSpecialNsResource( 'rdfs', 'subClassOf' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			Exporter::getEncodedPageName( new DIWikiPage( 'SomeTopCategory', NS_CATEGORY ) ),
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki',
			new DIWikiPage( 'SomeTopCategory', NS_CATEGORY )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			Exporter::getSpecialNsResource( 'rdfs', 'subClassOf' ),
			$exportData
		);
	}

	public function testExportSubobject() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$subobject = new Subobject( $semanticData->getSubject()->getTitle() );
		$subobject->setEmptyContainerForId( 'Foo' );

		$semanticData->addPropertyObjectValue(
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		$exportData = Exporter::makeExportData( $semanticData );

		$expectedProperty = new ExpNsResource(
			$this->transformPropertyLabelToAuxiliary( $subobject->getProperty() ),
			Exporter::getNamespaceUri( 'property' ),
			'property',
			new DIWikiPage( 'Has_subobject', SMW_NS_PROPERTY )
		);

		$this->assertTrue(
			Exporter::hasHelperExpElement( $subobject->getProperty() )
		);

		$this->assertCount(
			1,
			$exportData->getValues( $expectedProperty )
		);

		$this->exportDataValidator->assertThatExportDataContainsProperty(
			$expectedProperty,
			$exportData
		);

		$expectedResource = new ExpNsResource(
			Exporter::getEncodedPageName( $subobject->getSemanticData()->getSubject() ) . '-23' . 'Foo',
			Exporter::getNamespaceUri( 'wiki' ),
			'wiki',
			$subobject->getSemanticData()->getSubject()
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResource,
			$expectedProperty,
			$exportData
		);
	}

	public function testExportSubSemanticData() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$factsheet = $this->fixturesProvider->getFactsheet( 'berlin' );
		$factsheet->setTargetSubject( $semanticData->getSubject() );

		$demographicsSubobject = $factsheet->getDemographics();

		$semanticData->addPropertyObjectValue(
			$demographicsSubobject->getProperty(),
			$demographicsSubobject->getContainer()
		);

		$exportData = Exporter::makeExportData(
			$semanticData->findSubSemanticData( $demographicsSubobject->getSubobjectId() )
		);

		$this->assertCount(
			1,
			$exportData->getValues( Exporter::getSpecialPropertyResource( '_SKEY' ) )
		);

		$this->assertCount(
			1,
			$exportData->getValues( Exporter::getSpecialNsResource( 'swivt', 'wikiNamespace' ) )
		);
	}

	private function transformPropertyLabelToAuxiliary( DIProperty $property ) {
		return str_replace( ' ', '_', $property->getLabel() ) . '-23' . 'aux';
	}

}
