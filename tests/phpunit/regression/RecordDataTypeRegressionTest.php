<?php

namespace SMW\Test;

use SMW\DIWikiPage;
use SMW\DIProperty;

use Title;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group RegressionTest
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class RecordDataTypeRegressionTest extends MwRegressionTestCase {

	public function getSourceFile() {
		return __DIR__ . '/data/' . 'RecordDataTypeRegressionTest-Mw-1-19-7.xml';
	}

	public function acquirePoolOfTitles() {
		return array(
			'Property:Has record number field',
			'Property:Has record page field',
			'Property:Has record text field',
			'Property:Has record type',
			'Property:Has record type for single test',
			'RecordDataTypePage',
			'RecordDataTypeRegressionTest/WithSubpage',
			'RecordDataTypeRegressionTest'
		);
	}

	public function assertDataImport() {

		$title = Title::newFromText( 'RecordDataTypeRegressionTest' );

		$expectedCategoryAsWikiValue = array(
			'property' => new DIProperty( '_INST' ),
			'propertyValues' => array(
				'Regression test',
				'Data type regression test',
				'Record type regression test'
			)
		);

		$expectedSomeProperties = array(
			'properties' => array(
				DIProperty::newFromUserLabel( 'RecordDataTypePage' ),
				DIProperty::newFromUserLabel( 'BarText' ),
				DIProperty::newFromUserLabel( 'BooPage' ),
				DIProperty::newFromUserLabel( 'FooPage' ),
				DIProperty::newFromUserLabel( 'QyuPage' ),
				new DIProperty( '_ASK' ),
				new DIProperty( '_MDAT' ),
				new DIProperty( '_SKEY' ),
				new DIProperty( '_SOBJ' ),
				new DIProperty( '_INST' )
			)
		);

		$expectedRecordTypeValuesAsWikiValue = array(
			'property'       => DIProperty::newFromUserLabel( 'Has record type for single test' ),
			'propertyValues' => array(
				'ForSingleTestAsPage; ForSingleTestAsText; 3,333',
			)
		);

		$expectedRecordPageFieldValuesAsWikiValue = array(
			'subject'        => DIWikiPage::newFromTitle( $title ),
			'record'         => DIProperty::newFromUserLabel( 'Has record type' ),
			'property'       => DIProperty::newFromUserLabel( 'Has record page field' ),
			'propertyValues' => array(
				'FooPage',
				'QyuPageOnSubobject',
				'QyuPage',
				'XeuiPageOnSubobject',
				'RecordDataTypePage',
				'BooPage'
			)
		);

		$expectedRecordTextFieldValuesAsWikiValue = array(
			'subject'        => DIWikiPage::newFromTitle( $title ),
			'record'         => DIProperty::newFromUserLabel( 'Has record type' ),
			'property'       => DIProperty::newFromUserLabel( 'Has record text field' ),
			'propertyValues' => array(
				'BarText',
				'ForSingleTestAsText',
				'FooText',
				'XeuiTextOnSubobject'
			)
		);

		$expectedRecordNumberFieldValuesAsNumber = array(
			'subject'        => DIWikiPage::newFromTitle( Title::newFromText( 'RecordDataTypeRegressionTest/WithSubpage' ) ),
			'record'         => DIProperty::newFromUserLabel( 'Has record type' ),
			'property'       => DIProperty::newFromUserLabel( 'Has record number field' ),
			'propertyValues' => array(
				1111,
				9001,
				9999,
				1009
			)
		);

		$this->semanticDataValidator = new SemanticDataValidator;

		$semanticDataFinder = new ByPageSemanticDataFinder;
		$semanticDataFinder->setTitle( $title )->setStore( $this->getStore() );

		$semanticDataBatches = array(
			$semanticDataFinder->fetchFromOutput(),
			$semanticDataFinder->fetchFromStore()
		);

		foreach ( $semanticDataBatches as $semanticData ) {

			$this->semanticDataValidator->assertThatCategoriesAreSet(
				$expectedCategoryAsWikiValue,
				$semanticData
			);

			$this->semanticDataValidator->assertThatPropertiesAreSet(
				$expectedSomeProperties,
				$semanticData
			);

			$this->assertThatSemanticDataValuesAreSet( $expectedRecordTypeValuesAsWikiValue, $semanticData );
		}

		$this->assertThatRecordValuesAreSet( $expectedRecordPageFieldValuesAsWikiValue );
		$this->assertThatRecordValuesAreSet( $expectedRecordTextFieldValuesAsWikiValue );
		$this->assertThatRecordValuesAreSet( $expectedRecordNumberFieldValuesAsNumber );

	}

	protected function assertThatSemanticDataValuesAreSet( $expected, $semanticData ) {

		$runValueAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->equals( $expected['property'] ) ) {
				$runValueAssert = true;
				$this->semanticDataValidator->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

		}

		// $this->assertTrue( $runValueAssert, __METHOD__ );
	}

	protected function assertThatRecordValuesAreSet( $expected ) {

		$runValueAssert = false;
		$values = array();

		$countainer = $this->getStore()->getPropertyValues(
			$expected['subject'],
			$expected['record']
		);

		foreach ( $countainer as $record ) {
			$values = array_merge(
				$values,
				$this->getStore()->getPropertyValues( $record, $expected['property'] )
			);
		}

		$this->semanticDataValidator->assertThatPropertyValuesAreSet(
			$expected,
			$expected['property'],
			$values
		);

		// $this->assertTrue( $runValueAssert, __METHOD__ );
	}

}
