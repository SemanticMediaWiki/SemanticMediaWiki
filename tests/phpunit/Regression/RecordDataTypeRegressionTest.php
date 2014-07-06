<?php

namespace SMW\Tests\Regression;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\ByPageSemanticDataFinder;
use SMW\Test\MwRegressionTestCase;

use SMW\DIWikiPage;
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
 * @licence GNU GPL v2+
 * @since 1.9.1
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

		$property = DIProperty::newFromUserLabel( 'Has record type for single test' );
		$valueString = 'ForSingleTestAsPage;ForSingleTestAsText;3333';

		if ( $property->findPropertyTypeID() === '_rec' ) {
			$valueString = 'ForSingleTestAsPage; ForSingleTestAsText; 3,333';
		}

		$expectedRecordTypeValuesAsWikiValue = array(
			'subject'        => DIWikiPage::newFromTitle( $title ),
			'record'         => $property,
			'property'       => $property,
			'propertyValues' => array( $valueString, '?; ?; ?' )
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
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $title ) ),
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

		$this->assertThatRecordValuesAreSet( $expectedRecordTypeValuesAsWikiValue );
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

		// Issue #124 needs to be resolved first
		// $this->assertTrue( $runValueAssert, __METHOD__ );
	}

	protected function assertThatRecordValuesAreSet( $expected ) {

		$runValueAssert = false;
		$values = array();

		$container = $this->getStore()->getPropertyValues(
			$expected['subject'],
			$expected['record']
		);

		foreach ( $container as $record ) {
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

		// Issue #124 needs to be resolved first
		// $this->assertTrue( $runValueAssert, __METHOD__ );
	}

}
