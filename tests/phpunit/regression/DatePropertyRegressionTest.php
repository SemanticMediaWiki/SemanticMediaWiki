<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMw\SemanticData;
use SMW\ParserData;
use SMW\StoreFactory;
use SMW\DIWikiPage;
use SMWDataItem as DataItem;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class DatePropertyRegressionTest extends MwImporterTestBase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'DatePropertyRegressionTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			'DatePropertyRegressionTest',
			'Property:Has query date',
			'Property:Has calendar date',
			'Property:Has date'
		);
	}

	public function assertDataImport() {

		$expectedCategoryAsWikiValue = array(
			'propertyKey' => '_INST',
			'propertyValue' => array(
				'Regression test'
			)
		);

		$expectedProperties = array(
			'propertyKey' => array(
				'Has_date',
				'Has_calendar_date',
				'Has_query_date',
				'_ASK',
				'_LEDT',
				'_MDAT',
				'_SKEY',
				'_SOBJ',
				'_INST'
			)
		);

		$expectedDateValuesAsISO = array(
			'valueFormatter' => $this->setISO8601DateValueFormatter(),
			'propertyLabel'  => 'Has query date',
			'propertyValue'  => array(
				'2010-01-04T19:00:00',
				'2011-06-08',
				'1980-01-01',
				'2000-02-11T10:00:00',
				'2000-02-03'
			)
		);

		$expectedCalendarSpecificDateValuesAsISO = array(
			'valueFormatter' => $this->setISO8601DateValueFormatter(),
			'propertyLabel'  => 'Has calendar date',
			'propertyValue'  => array(
				'--301-12-28', // 1 January 300 BC
				'--2147483647-01-01', // 2147483647 BC
				'2000-02-24',
				'1492-02-11'
			)
		);

		$expectedCalendarSpecificDateValuesAsWikiValue = array(
			'valueFormatter' => $this->setWikiValueDateValueFormatter(),
			'propertyLabel'  => 'Has calendar date',
			'propertyValue'  => array(
				'1 January 300 BC', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'24 February 2000',
				'2 February 1492'
			)
		);

		$expectedCalendarSpecificDateValuesAsWikiValueWithGRCalendarModel = array(
			'valueFormatter' => $this->setWikiValueDateWithGRCalendarModelValueFormatter(),
			'propertyLabel'  => 'Has calendar date',
			'propertyValue'  => array(
				'28 December 301 BC', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'24 February 2000',
				'11 February 1492'
			)
		);

		$expectedCalendarSpecificDateValuesAsWikiValueWithJLCalendarModel = array(
			'valueFormatter' => $this->setWikiValueDateWithJLCalendarModelValueFormatter(),
			'propertyLabel'  => 'Has calendar date',
			'propertyValue'  => array(
				'1 January 300 BC', // 1 January 300 BC
				'2147483647 BC', // 2147483647 BC
				'11 February 2000',
				'2 February 1492'
			)
		);

		$expectedDateValuesAsMediaWiki = array(
			'valueFormatter' => $this->setMediaWikiDateValueFormatter(),
			'propertyLabel'  => 'Has query date',
			'propertyValue'  => array(
				'19:00, 4 January 2010',
				'8 June 2011',
				'1 January 1980',
				'10:00, 11 February 2000',
				'3 February 2000'
			)
		);

		$title = Title::newFromText( 'DatePropertyRegressionTest' );

		$semanticDataFetcher = $this->newSemanticDataFetcher()
			->setTitle( $title )
			->setStore( $this->getStore() );

		$this->assertSemanticData(
			$expectedCategoryAsWikiValue,
			$expectedProperties,
			$semanticDataFetcher->fetchFromOutput()
		);

		$this->assertSemanticData(
			$expectedCategoryAsWikiValue,
			$expectedProperties,
			$semanticDataFetcher->fetchFromStore()
		);

		$this->assertThatDateValuesAreSet(
			$expectedDateValuesAsISO,
			$semanticDataFetcher->fetchFromOutput()
		);

		$this->assertThatDateValuesAreSet(
			$expectedDateValuesAsMediaWiki,
			$semanticDataFetcher->fetchFromOutput()
		);

		$this->assertThatDateValuesAreSet(
			$expectedCalendarSpecificDateValuesAsISO,
			$semanticDataFetcher->fetchFromStore()
		);

		$this->assertThatDateValuesAreSet(
			$expectedCalendarSpecificDateValuesAsWikiValue,
			$semanticDataFetcher->fetchFromOutput()
		);

		$this->assertThatDateValuesAreSet(
			$expectedCalendarSpecificDateValuesAsWikiValueWithGRCalendarModel,
			$semanticDataFetcher->fetchFromStore()
		);

		$this->assertThatDateValuesAreSet(
			$expectedCalendarSpecificDateValuesAsWikiValueWithJLCalendarModel,
			$semanticDataFetcher->fetchFromOutput()
		);

	}

	protected function assertSemanticData( $expectedCategories, $expectedProperties, $semanticData ) {
		$this->newSemanticDataAsserts()->assertThatCategoriesAreSet( $expectedCategories, $semanticData );
		$this->newSemanticDataAsserts()->assertThatPropertiesAreSet( $expectedProperties, $semanticData );
	}

	protected function assertThatDateValuesAreSet( $expected, $semanticData ) {

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->findPropertyTypeID() === '_dat' && $expected['propertyLabel'] === $property->getLabel() ) {
				$this->newSemanticDataAsserts()->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

		}
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
