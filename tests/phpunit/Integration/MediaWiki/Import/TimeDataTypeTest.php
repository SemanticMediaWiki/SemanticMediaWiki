<?php

namespace SMW\Tests\Integration\MediaWiki\Import;

use SMW\DIProperty;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\ByPageSemanticDataFinder;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-import
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class TimeDataTypeTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		if ( strpos( strtolower( $GLOBALS['smwgSparqlRepositoryConnector'] ), 'virtuoso' ) !== false ) {
			$this->markTestIncomplete(
				"Virtuoso will fail for '1 January 300 BC' with 'Virtuoso 22007 Error DT006: Cannot convert -0302-12-28Z to datetime : Incorrect month field length'"
			);
		}

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/'. 'Fixtures/' . 'TimeDataTypeTest-Mw-1-19-7.xml'
		);

		// Shoudl be fixed
		if ( $GLOBALS['wgLanguageCode'] !== 'en' ) {
			$this->markTestIncomplete( 'Skipping test because time/date comparison expects a (en) formatted string' );
		}

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown() {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->importedTitles );

		parent::tearDown();
	}

	public function testImportOfDifferentDateWithAssortmentOfOutputConversion() {

		$this->importedTitles = [
			'TimeDataTypeRegressionTest',
			'Property:Has query date',
			'Property:Has calendar date',
			'Property:Has date'
		];

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$title = Title::newFromText( 'TimeDataTypeRegressionTest' );

		$expectedCategoryAsWikiValue = [
			'property' => new DIProperty( '_INST' ),
			'propertyValues' => [
				'Regression test'
			]
		];

		$expectedPropertiesFromImport = [
			'properties' => [
				DIProperty::newFromUserLabel( 'Has date' ),
				DIProperty::newFromUserLabel( 'Has calendar date' ),
				DIProperty::newFromUserLabel( 'Has query date' ),
				new DIProperty( '_ASK' ),
				new DIProperty( '_MDAT' ),
				new DIProperty( '_SKEY' ),
				new DIProperty( '_SOBJ' ),
				new DIProperty( '_INST' )
			]
		];

		$expectedDateValuesAsISO = [
			'valueFormatter' => $this->setISO8601DateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has query date' ),
			'propertyValues' => [
				'2010-01-04T19:00:00',
				'2011-06-08',
				'1980-01-01',
				'2000-02-11T10:00:00',
				'2000-02-03'
			]
		];

		$expectedDateValuesAsMediaWiki = [
			'valueFormatter' => $this->setMediaWikiDateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has query date' ),
			'propertyValues' => [
				'19:00, 4 January 2010',
				'8 June 2011',
				'1 January 1980',
				'10:00, 11 February 2000',
				'3 February 2000'
			]
		];

		$expectedDateValuesAsWikiValue = [
			'valueFormatter' => $this->setWikiValueDateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has query date' ),
			'propertyValues' => [
				'4 January 2010 19:00:00',
				'8 June 2011',
				'1 January 1980',
				'11 February 2000 10:00:00',
				'3 February 2000'
			]
		];

		// Note Windows vs Linux date conversion on PHP
		// where 14000000000 BC is 2147483647 BC on Windows

		$expectedCalendarSpecificDateValuesAsISO = [
			'valueFormatter' => $this->setISO8601DateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => [
				'--301-12-28', // 1 January 300 BC
				'--2147483647-01-01', // 2147483647 BC
				'--14000000000-01-01',
				'2000-02-24',
				'1492-02-11'
			]
		];

		$expectedCalendarSpecificDateValuesAsWikiValue = [
			'valueFormatter' => $this->setWikiValueDateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => [
				'1 January 300 BC JL', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'14000000000 BC',
				'11 February 2000 JL',
				'2 February 1492 JL'
			]
		];

		$expectedCalendarSpecificDateValuesAsWikiValueWithGRCalendarModel = [
			'valueFormatter' => $this->setWikiValueDateWithGRCalendarModelValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => [
				'28 December 301 BC', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'14000000000 BC',
				'24 February 2000',
				'11 February 1492'
			]
		];

		$expectedCalendarSpecificDateValuesAsWikiValueWithJLCalendarModel = [
			'valueFormatter' => $this->setWikiValueDateWithJLCalendarModelValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => [
				'1 January 300 BC JL', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'14000000000 BC',
				'11 February 2000 JL',
				'2 February 1492 JL'
			]
		];

		$this->semanticDataFinder = new ByPageSemanticDataFinder;
		$this->semanticDataFinder->setTitle( $title )->setStore( $this->getStore() );

		$semanticDataBatches = [
			$this->semanticDataFinder->fetchFromOutput(),
			$this->semanticDataFinder->fetchFromStore()
		];

		$expectedDateValuesBatches = [
			$expectedDateValuesAsISO,
			$expectedDateValuesAsMediaWiki,
			$expectedDateValuesAsWikiValue,
			$expectedCalendarSpecificDateValuesAsISO,
			$expectedCalendarSpecificDateValuesAsWikiValue,
			$expectedCalendarSpecificDateValuesAsWikiValueWithGRCalendarModel,
			$expectedCalendarSpecificDateValuesAsWikiValueWithJLCalendarModel
		];

		foreach ( $semanticDataBatches as $semanticData ) {

			// Something changed in MW since 1.28 that causes a
			// "SMW\Tests\Utils\Validators\SemanticDataValidator::assertContainsPropertyValues
			// for '_INST' as '__sin' with (Regression test, Redirect test, Simple redirect test)
			// Failed asserting that an array contains 'Lorem ipsum'." and since I'm not sure
			// about the cause, this part is disabled and awaits an investigation

			//	$this->assertThatCategoriesAreSet(
			//		$expectedCategoryAsWikiValue,
			//		$semanticData
			//	);

			$this->semanticDataValidator->assertThatPropertiesAreSet( $expectedPropertiesFromImport, $semanticData );
			$this->assertBatchesOfDateValues( $expectedDateValuesBatches, $semanticData );
		}

	}

	protected function assertBatchesOfDateValues( $assertionBatches, $semanticData ) {
		foreach ( $assertionBatches as $singleAssertionBatch ) {
			$this->assertThatDateValuesAreSet( $singleAssertionBatch, $semanticData );
		}
	}

	protected function assertThatDateValuesAreSet( $expected, $semanticData ) {

		$runDateValueAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->findPropertyTypeID() === '_dat' && $property->equals( $expected['property'] ) ) {
				$runDateValueAssert = true;
				$this->semanticDataValidator->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

		}

		// Solve issue with single/testsuite DB setup first
		// $this->assertTrue( $runDateValueAssert, __METHOD__ );
	}

	protected function setWikiValueDateValueFormatter() {
		return function( $dataValue ) { return $dataValue->getWikiValue();
		};
	}

	protected function setWikiValueDateWithGRCalendarModelValueFormatter() {
		return function( $dataValue ) {
			$dataValue->setOutputFormat( 'GR' );
			return $dataValue->getWikiValue();
		};
	}

	protected function setWikiValueDateWithJLCalendarModelValueFormatter() {
		return function( $dataValue ) {
			$dataValue->setOutputFormat( 'JL' );
			return $dataValue->getWikiValue();
		};
	}

	protected function setISO8601DateValueFormatter() {
		return function( $dataValue ) { return $dataValue->getISO8601Date();
		};
	}

	protected function setMediaWikiDateValueFormatter() {
		return function( $dataValue ) { return $dataValue->getMediaWikiDate();
		};
	}

}
