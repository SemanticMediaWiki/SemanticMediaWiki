<?php

namespace SMW\Tests\Export;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Escaper;
use SMW\Subobject;
use SMW\Tests\Utils\Fixtures\FixturesProvider;
use SMW\Tests\Utils\SemanticDataFactory;
use SMW\Tests\Utils\Validators\ExportDataValidator;
use SMWExporter as Exporter;

/**
 * @covers \SMWExporter
 *
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ExportSemanticDataTest extends \PHPUnit\Framework\TestCase {

	private $semanticDataFactory;
	private $dataValueFactory;
	private $exportDataValidator;
	private $fixturesProvider;

	protected function setUp(): void {
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

		$exporter = Exporter::getInstance();

		$exportData = $exporter->makeExportData( $semanticData );

		$this->assertCount(
			1,
			$exportData->getValues( $exporter->newExpNsResourceById( 'swivt', 'redirectsTo' ) )
		);

		$this->assertCount(
			1,
			$exportData->getValues( $exporter->newExpNsResourceById( 'owl', 'sameAs' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			'FooRedirectTarget',
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
			'wiki',
			$redirectTarget
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			$exporter->newExpNsResourceById( 'owl', 'sameAs' ),
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

		$exportData = Exporter::getInstance()->makeExportData( $semanticData );

		$expectedProperty = new ExpNsResource(
			Escaper::encodePage( $property->getDiWikiPage() ),
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
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
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
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

		$exportData = Exporter::getInstance()->makeExportData( $semanticData );

		$expectedProperty = new ExpNsResource(
			'A123',
			Exporter::getInstance()->getNamespaceUri( 'property' ),
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
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
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
			$this->dataValueFactory->newDataValueByProperty( new DIProperty( '_SUBP' ), 'SomeTopProperty' )
		);

		$exporter = Exporter::getInstance();

		$exportData = $exporter->makeExportData( $semanticData );

		$this->assertCount(
			1,
			$exportData->getValues( $exporter->newExpNsResourceById( 'rdfs', 'subPropertyOf' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			'SomeTopProperty',
			Exporter::getInstance()->getNamespaceUri( 'property' ),
			'property',
			new DIWikiPage( 'SomeTopProperty', SMW_NS_PROPERTY )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			$exporter->newExpNsResourceById( 'rdfs', 'subPropertyOf' ),
			$exportData
		);
	}

	public function testExportCategory() {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataValueByProperty( new DIProperty( '_INST' ), 'SomeCategory' )
		);

		$exporter = Exporter::getInstance();

		$exportData = $exporter->makeExportData( $semanticData );

		$this->assertCount(
			2,
			$exportData->getValues( $exporter->newExpNsResourceById( 'rdf', 'type' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			'SomeCategory',
			Exporter::getInstance()->getNamespaceUri( 'category' ),
			'category',
			new DIWikiPage( 'SomeCategory', NS_CATEGORY )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			$exporter->newExpNsResourceById( 'rdf', 'type' ),
			$exportData
		);
	}

	public function testExportSubcategory() {
		$semanticData = $this->semanticDataFactory
			->setSubject( new DIWikiPage( 'SomeSubcategory', NS_CATEGORY ) )
			->newEmptySemanticData();

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataValueByProperty( new DIProperty( '_SUBC' ), 'SomeTopCategory' )
		);

		$exporter = Exporter::getInstance();

		$exportData = $exporter->makeExportData( $semanticData );

		$this->assertCount(
			1,
			$exportData->getValues( $exporter->newExpNsResourceById( 'rdfs', 'subClassOf' ) )
		);

		$expectedResourceElement = new ExpNsResource(
			'SomeTopCategory',
			Exporter::getInstance()->getNamespaceUri( 'category' ),
			'category',
			new DIWikiPage( 'SomeTopCategory', NS_CATEGORY )
		);

		$this->exportDataValidator->assertThatExportDataContainsResource(
			$expectedResourceElement,
			$exporter->newExpNsResourceById( 'rdfs', 'subClassOf' ),
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

		$exportData = Exporter::getInstance()->makeExportData( $semanticData );

		$expectedProperty = new ExpNsResource(
			$this->transformPropertyLabelToAuxiliary( $subobject->getProperty() ),
			Exporter::getInstance()->getNamespaceUri( 'property' ),
			'property',
			new DIWikiPage( 'Has_subobject', SMW_NS_PROPERTY )
		);

		$this->assertTrue(
			Exporter::getInstance()->hasHelperExpElement( $subobject->getProperty() )
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
			Escaper::encodePage( $subobject->getSemanticData()->getSubject() ) . '-23' . 'Foo',
			Exporter::getInstance()->getNamespaceUri( 'wiki' ),
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

		$exporter = Exporter::getInstance();

		$exportData = $exporter->makeExportData(
			$semanticData->findSubSemanticData( $demographicsSubobject->getSubobjectId() )
		);

		$this->assertCount(
			1,
			$exportData->getValues( $exporter->getSpecialPropertyResource( '_SKEY' ) )
		);

		$this->assertCount(
			1,
			$exportData->getValues( $exporter->newExpNsResourceById( 'swivt', 'wikiNamespace' ) )
		);
	}

	private function transformPropertyLabelToAuxiliary( DIProperty $property ) {
		return str_replace( ' ', '_', $property->getLabel() );
	}

}
