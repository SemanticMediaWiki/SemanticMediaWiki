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

	public function getTitles() {
		return array(
			'DatePropertyRegressionTest',
			'Property:Has date for query',
			'Property:Has date'
		);
	}

	public function assertSemanticData() {

		$expectedProperties = array(
			'propertyKey' => array(
				'Has_date',
				'Has_date_for_query',
				'_ASK',
				'_LEDT',
				'_MDAT',
				'_SKEY',
				'_SOBJ',
				'_INST'
			)
		);

		$expectedDateValues = array(
			'propertyLabel' => 'Has date for query',
			'propertyValue' => array(
				'2010-01-04T19:00:00',
				'2011-06-08',
				'1980-01-01'
			)
		);

		$title = Title::newFromText( 'DatePropertyRegressionTest' );

		$this->assertSemanticDataFromSource(
			$expectedProperties,
			$expectedDateValues,
			$this->fetchSemanticDataFromOutput( $title )
		);

		$this->assertSemanticDataFromSource(
			$expectedProperties,
			$expectedDateValues,
			$this->fetchSemanticDataFromStore( $title )
		);

	}

	protected function assertSemanticDataFromSource( $expectedProperties, $expectedDateValues, $semanticData ) {
		$this->assertPropertiesAreSet( $expectedProperties, $semanticData );
		$this->assertDateProperty( $expectedDateValues, $semanticData );
	}

	protected function assertDateProperty( $expected, $semanticData ) {

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->findPropertyTypeID() === '_dat' && $expected['propertyLabel'] === $property->getLabel() ) {
				$this->assertPropertyValues( $expected , $property, $semanticData->getPropertyValues( $property ) );
			}

		}
	}

	protected function assertPropertyValues( $expected, $property, $dataItems ) {

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataItemValue( $dataItem, $property );

			$this->assertContains(
				$dataValue->getISO8601Date(),
				$expected['propertyValue'],
				"Asserts to contain a property value"
			);
		}
	}

}
