<?php

namespace SMW\Tests\Integration\MediaWiki\Import;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\ByPageSemanticDataFinder;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-import
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class RecordDataTypeTest extends MwDBaseUnitTestCase {

	protected $destroyDatabaseTablesAfterRun = true;

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/'. 'Fixtures/' . 'RecordDataTypeTest-Mw-1-19-7.xml'
		);

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

	public function testImportOfRecordValues() {

		$this->importedTitles = [
			'Property:Has record number field',
			'Property:Has record page field',
			'Property:Has record text field',
			'Property:Has record type',
			'Property:Has record type for single test',
			'RecordDataTypePage',
			'RecordDataTypeRegressionTest/WithSubpage',
			'RecordDataTypeRegressionTest'
		];

		$this->titleValidator->assertThatTitleIsKnown( $this->importedTitles );

		$title = Title::newFromText( 'RecordDataTypeRegressionTest' );

		$expectedCategoryAsWikiValue = [
			'property' => new DIProperty( '_INST' ),
			'propertyValues' => [
				'Regression test',
				'Data type regression test',
				'Record type regression test'
			]
		];

		$expectedSomeProperties = [
			'properties' => [
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
			]
		];

		$property = DIProperty::newFromUserLabel( 'Has record type for single test' );
		$valueString = 'ForSingleTestAsPage;ForSingleTestAsText;3333';

		if ( $property->findPropertyTypeID() === '_rec' ) {
			$valueString = 'ForSingleTestAsPage; ForSingleTestAsText; 3333';
		}

		$expectedRecordTypeValuesAsWikiValue = [
			'subject'        => DIWikiPage::newFromTitle( $title ),
			'record'         => $property,
			'property'       => $property,
			'propertyValues' => [ $valueString, '?; ?; ?' ]
		];

		$expectedRecordPageFieldValuesAsWikiValue = [
			'subject'        => DIWikiPage::newFromTitle( $title ),
			'record'         => DIProperty::newFromUserLabel( 'Has record type' ),
			'property'       => DIProperty::newFromUserLabel( 'Has record page field' ),
			'propertyValues' => [
				'FooPage',
				'QyuPageOnSubobject',
				'QyuPage',
				'XeuiPageOnSubobject',
				'RecordDataTypePage',
				'BooPage'
			]
		];

		$expectedRecordTextFieldValuesAsWikiValue = [
			'subject'        => DIWikiPage::newFromTitle( $title ),
			'record'         => DIProperty::newFromUserLabel( 'Has record type' ),
			'property'       => DIProperty::newFromUserLabel( 'Has record text field' ),
			'propertyValues' => [
				'BarText',
				'ForSingleTestAsText',
				'FooText',
				'XeuiTextOnSubobject'
			]
		];

		$expectedRecordNumberFieldValuesAsNumber = [
			'subject'        => DIWikiPage::newFromTitle( Title::newFromText( 'RecordDataTypeRegressionTest/WithSubpage' ) ),
			'record'         => DIProperty::newFromUserLabel( 'Has record type' ),
			'property'       => DIProperty::newFromUserLabel( 'Has record number field' ),
			'propertyValues' => [
				1111,
				9001,
				9999,
				1009
			]
		];

		$semanticDataFinder = new ByPageSemanticDataFinder;
		$semanticDataFinder->setTitle( $title )->setStore( $this->getStore() );

		$semanticDataBatches = [
			$this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $title ) ),
		];

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
		$values = [];

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
