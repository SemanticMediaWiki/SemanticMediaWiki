<?php

namespace SMW\Tests\Regression;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\ByPageSemanticDataFinder;
use SMW\Test\MwRegressionTestCase;

use SMW\DIProperty;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-regression
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class TimeDataTypeRegressionTest extends MwRegressionTestCase {

	/**
	 * FIXME
	 */
	protected $storesToBeExcluded = array( 'SMWSparqlStore' );

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'TimeDataTypeRegressionTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			'TimeDataTypeRegressionTest',
			'Property:Has query date',
			'Property:Has calendar date',
			'Property:Has date'
		);
	}

	public function assertDataImport() {

		$title = Title::newFromText( 'TimeDataTypeRegressionTest' );

		$this->assertTrue( $title->exists() );

		$expectedCategoryAsWikiValue = array(
			'property' => new DIProperty( '_INST' ),
			'propertyValues' => array(
				'Regression test'
			)
		);

		$expectedPropertiesFromImport = array(
			'properties' => array(
				DIProperty::newFromUserLabel( 'Has date' ),
				DIProperty::newFromUserLabel( 'Has calendar date' ),
				DIProperty::newFromUserLabel( 'Has query date' ),
				new DIProperty( '_ASK' ),
				new DIProperty( '_MDAT' ),
				new DIProperty( '_SKEY' ),
				new DIProperty( '_SOBJ' ),
				new DIProperty( '_INST' )
			)
		);

		$expectedDateValuesAsISO = array(
			'valueFormatter' => $this->setISO8601DateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has query date' ),
			'propertyValues' => array(
				'2010-01-04T19:00:00',
				'2011-06-08',
				'1980-01-01',
				'2000-02-11T10:00:00',
				'2000-02-03'
			)
		);

		$expectedDateValuesAsMediaWiki = array(
			'valueFormatter' => $this->setMediaWikiDateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has query date' ),
			'propertyValues' => array(
				'19:00, 4 January 2010',
				'8 June 2011',
				'1 January 1980',
				'10:00, 11 February 2000',
				'3 February 2000'
			)
		);

		$expectedDateValuesAsWikiValue = array(
			'valueFormatter' => $this->setWikiValueDateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has query date' ),
			'propertyValues' => array(
				'4 January 2010 19:00:00',
				'8 June 2011',
				'1 January 1980',
				'11 February 2000 10:00:00',
				'3 February 2000'
			)
		);

		$expectedCalendarSpecificDateValuesAsISO = array(
			'valueFormatter' => $this->setISO8601DateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => array(
				'--301-12-28', // 1 January 300 BC
				'--2147483647-01-01', // 2147483647 BC
				'2000-02-24',
				'1492-02-11'
			)
		);

		$expectedCalendarSpecificDateValuesAsWikiValue = array(
			'valueFormatter' => $this->setWikiValueDateValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => array(
				'1 January 300 BC', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'24 February 2000',
				'2 February 1492'
			)
		);

		$expectedCalendarSpecificDateValuesAsWikiValueWithGRCalendarModel = array(
			'valueFormatter' => $this->setWikiValueDateWithGRCalendarModelValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => array(
				'28 December 301 BC', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'24 February 2000',
				'11 February 1492'
			)
		);

		$expectedCalendarSpecificDateValuesAsWikiValueWithJLCalendarModel = array(
			'valueFormatter' => $this->setWikiValueDateWithJLCalendarModelValueFormatter(),
			'property'       => DIProperty::newFromUserLabel( 'Has calendar date' ),
			'propertyValues' => array(
				'1 January 300 BC', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'11 February 2000',
				'2 February 1492'
			)
		);

		$this->semanticDataValidator = new SemanticDataValidator;

		$this->semanticDataFinder = new ByPageSemanticDataFinder;
		$this->semanticDataFinder->setTitle( $title )->setStore( $this->getStore() );

		$semanticDataBatches = array(
			$this->semanticDataFinder->fetchFromOutput(),
			$this->semanticDataFinder->fetchFromStore()
		);

		$expectedDateValuesBatches = array(
			$expectedDateValuesAsISO,
			$expectedDateValuesAsMediaWiki,
			$expectedDateValuesAsWikiValue,
			$expectedCalendarSpecificDateValuesAsISO,
			$expectedCalendarSpecificDateValuesAsWikiValue,
			$expectedCalendarSpecificDateValuesAsWikiValueWithGRCalendarModel,
			$expectedCalendarSpecificDateValuesAsWikiValueWithJLCalendarModel
		);

		foreach ( $semanticDataBatches as $semanticData ) {
			$this->semanticDataValidator->assertThatCategoriesAreSet( $expectedCategoryAsWikiValue, $semanticData );
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
		return function( $dataValue ) { return $dataValue->getWikiValue(); };
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
		return function( $dataValue ) { return $dataValue->getISO8601Date(); };
	}

	protected function setMediaWikiDateValueFormatter() {
		return function( $dataValue ) { return $dataValue->getMediaWikiDate(); };
	}

}
