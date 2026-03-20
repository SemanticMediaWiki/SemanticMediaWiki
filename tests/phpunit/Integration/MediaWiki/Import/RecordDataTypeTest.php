<?php

namespace SMW\Tests\Integration\MediaWiki\Import;

use MediaWiki\MediaWikiServices;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\ByPageSemanticDataFinder;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-import
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9.1
 *
 * @author mwjames
 */
class RecordDataTypeTest extends SMWIntegrationTestCase {

	private $importedTitles = [];
	private $runnerFactory;
	private $titleValidator;
	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->runnerFactory  = UtilityFactory::getInstance()->newRunnerFactory();
		$this->titleValidator = UtilityFactory::getInstance()->newValidatorFactory()->newTitleValidator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$importRunner = $this->runnerFactory->newXmlImportRunner(
			__DIR__ . '/' . 'Fixtures/' . 'RecordDataTypeTest-Mw-1-19-7.xml'
		);

		if ( !$importRunner->setVerbose( true )->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}
	}

	protected function tearDown(): void {
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

		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$title = $titleFactory->newFromText( 'RecordDataTypeRegressionTest' );

		$expectedCategoryAsWikiValue = [
			'property' => new Property( '_INST' ),
			'propertyValues' => [
				'Category:Regression test',
				'Category:Data type regression test',
				'Category:Record type regression test'
			]
		];

		$expectedSomeProperties = [
			'properties' => [
				Property::newFromUserLabel( 'RecordDataTypePage' ),
				Property::newFromUserLabel( 'BarText' ),
				Property::newFromUserLabel( 'BooPage' ),
				Property::newFromUserLabel( 'FooPage' ),
				Property::newFromUserLabel( 'QyuPage' ),
				new Property( '_ASK' ),
				new Property( '_MDAT' ),
				new Property( '_SKEY' ),
				new Property( '_SOBJ' ),
				new Property( '_INST' )
			]
		];

		$property = Property::newFromUserLabel( 'Has record type for single test' );
		$valueString = 'ForSingleTestAsPage;ForSingleTestAsText;3333';

		if ( $property->findPropertyTypeID() === '_rec' ) {
			$valueString = 'ForSingleTestAsPage; ForSingleTestAsText; 3333';
		}

		$expectedRecordTypeValuesAsWikiValue = [
			'subject'        => WikiPage::newFromTitle( $title ),
			'record'         => $property,
			'property'       => $property,
			'propertyValues' => [ $valueString, '?; ?; ?' ]
		];

		$expectedRecordPageFieldValuesAsWikiValue = [
			'subject'        => WikiPage::newFromTitle( $title ),
			'record'         => Property::newFromUserLabel( 'Has record type' ),
			'property'       => Property::newFromUserLabel( 'Has record page field' ),
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
			'subject'        => WikiPage::newFromTitle( $title ),
			'record'         => Property::newFromUserLabel( 'Has record type' ),
			'property'       => Property::newFromUserLabel( 'Has record text field' ),
			'propertyValues' => [
				'BarText',
				'ForSingleTestAsText',
				'FooText',
				'XeuiTextOnSubobject'
			]
		];

		$expectedRecordNumberFieldValuesAsNumber = [
			'subject'        => WikiPage::newFromTitle( $titleFactory->newFromText( 'RecordDataTypeRegressionTest/WithSubpage' ) ),
			'record'         => Property::newFromUserLabel( 'Has record type' ),
			'property'       => Property::newFromUserLabel( 'Has record number field' ),
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
			$this->getStore()->getSemanticData( WikiPage::newFromTitle( $title ) ),
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
