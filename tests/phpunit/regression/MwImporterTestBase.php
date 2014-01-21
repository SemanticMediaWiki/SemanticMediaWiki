<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\StoreFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;

use SMWDataItem as DataItem;

use WikiPage;
use Title;
use User;

use RuntimeException;

/**
 * MwImporterTestBase being used mostly to run regression and integration tests
 * in order to verify components such as hooks or parser functions to work as
 * specified
 *
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
abstract class MwImporterTestBase extends \MediaWikiTestCase {

	protected $enabledDB = false;
	protected $expectedAssertions = 0;

	/**
	 * @see MediaWikiTestCase::run
	 *
	 * Only where teardownTestDB is available (excludes 1.19/1.20 or you need to
	 * run phpunit ... --use-normal-tables) we are able to rebuild the DB (in
	 * order to exclude temporary table usage) otherwise some tests will fail with
	 * "Error: 1137 Can't reopen table" on MySQL (see Issue #80)
	 */
	function run( \PHPUnit_Framework_TestResult $result = null ) {

		if ( method_exists( $this, 'teardownTestDB' ) ) {
			$this->enabledDB = true;
			$this->teardownTestDB();
			$this->setCliArg( 'use-normal-tables', true );
		}

		parent::run( $result );
	}

	/**
	 * Specifies the import source file
	 *
	 * @return string
	 */
	public abstract function getSourceFile();

	/**
	 * Specifies a pool of titles expected to be imported
	 *
	 * @return array
	 */
	public abstract function acquirePoolOfTitles();

	/**
	 * Main assert method which is implemented by the subclass and contains all
	 * individual asserts expected from the import to be passed
	 */
	public abstract function assertDataImport();

	/**
	 * Main test for the data import, it is also the only test run in order
	 * to avoid having to import content several times (which is costly time
	 * and resource wise)
	 *
	 * The test is designed that when one assert fails the whole test fails as
	 * we are aiming to test an integration of a complete solution rather than
	 * its individual parts
	 *
	 * It is suggested not to run other "test..." components unless you run a
	 * re-import of content since each individual test will tear down imported
	 * content
	 */
	public function testDataImport() {

		$this->assertTitleIsNotKnownBeforeImport( $this->acquirePoolOfTitles() );

		$importer = new MwImporter( $this->getSourceFile() );
		$importer->setVerbose( true );

		$result = $importer->run();

		if ( !$result->isGood() ) {
			$importer->reportFailedImport();
		}

		if ( $this->isEnabledDatabase() ) {
			$this->assertTitleIsKnownAfterImport( $this->acquirePoolOfTitles() );
			$this->assertDataImport();
		}

	}

	protected function assertTitleIsNotKnownBeforeImport( $titles ) {
		$this->assertTitleExists( false, $titles );
	}

	protected function assertTitleIsKnownAfterImport( $titles ) {
		$this->assertTitleExists( true, $titles );
	}

	protected function getStore() {
		return StoreFactory::getStore();
	}

	protected function fetchSemanticDataFromStore( Title $title ) {
		return $this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $title ) );
	}

	protected function fetchSemanticDataFromOutput( Title $title ) {
		$contentFetcher = new ContentFetcher( $title );
		$parserData = new ParserData( $title, $contentFetcher->fetchOutput() );
		return $parserData->getData();
	}

	protected function assertPropertiesAreSet( array $expected, SemanticData $semanticData ) {

		$runPropertyAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			$this->assertInstanceOf( '\SMW\DIProperty', $property );

			if ( isset( $expected['propertyKey']) ){
				$runPropertyAssert = true;

				$this->assertContains(
					$property->getKey(),
					$expected['propertyKey'],
					'Asserts that the SemanticData container contains a specific property key'
				);
			}

			if ( isset( $expected['propertyLabel']) ){
				$runPropertyAssert = true;

				$this->assertContains(
					$property->getLabel(),
					$expected['propertyLabel'],
					'Asserts that the SemanticData container contains a specific property label'
				);
			}

		}

		$this->assertTrue( $runPropertyAssert, 'Assert that properties were checked' );

	}

	protected function assertPropertyValuesAreSet( array $expected, DIProperty $property, $dataItems ) {

		$runPropertyValueAssert = false;

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataItemValue( $dataItem, $property );
			$DIType = $dataValue->getDataItem()->getDIType();

			if ( $DIType === DataItem::TYPE_WIKIPAGE ) {
				$runPropertyValueAssert = true;

				$this->assertContains(
					$dataValue->getWikiValue(),
					$expected['propertyValue'],
					'Asserts that the SemanticData contains a property value of TYPE_WIKIPAGE'
				);

			} else if ( $DIType === DataItem::TYPE_NUMBER ) {
				$runPropertyValueAssert = true;

				$this->assertContains(
					$dataValue->getNumber(),
					$expected['propertyValue'],
					'Asserts that the SemanticData contains a property value of TYPE_NUMBER'
				);

			} else if ( $DIType === DataItem::TYPE_TIME ) {
				$runPropertyValueAssert = true;

				$this->assertContains(
					$dataValue->getISO8601Date(),
					$expected['propertyValue'],
					'Asserts that the SemanticData contains a property value of TYPE_TIME'
				);

			} else if ( $DIType === DataItem::TYPE_BLOB ) {
				$runPropertyValueAssert = true;

				$this->assertContains(
					$dataValue->getWikiValue(),
					$expected['propertyValue'],
					'Asserts that the SemanticData contains a property value of TYPE_BLOB'
				);

			}

		}

		$this->assertTrue( $runPropertyValueAssert, 'Assert that property values were checked' );
	}

	protected function assertCategoryInstance( $expected, $semanticData ) {

		$runCategoryInstanceAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->getKey() === DIProperty::TYPE_CATEGORY && $property->getKey() === $expected['propertyKey'] ) {
				$runCategoryInstanceAssert = true;

				$this->assertPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

			if ( $property->getKey() === DIProperty::TYPE_SUBCATEGORY && $property->getKey() === $expected['propertyKey'] ) {
				$runCategoryInstanceAssert = true;

				$this->assertPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

		}

		$this->assertTrue( $runCategoryInstanceAssert, 'Assert that a category instance were checked' );
	}

	protected function isEnabledDatabase() {

		if ( !$this->enabledDB ) {
			$this->markTestSkipped(
				'DB setup did not satisfy the test requirements (probably MW 1.19/1.20)'
			);
		}

		return true;
	}

	private function assertTitleExists( $expected, $titles ) {
		foreach ( $titles as $title ) {
			$this->assertEquals(
				$expected,
				Title::newFromText( $title )->exists(),
				"Assert title {$title}"
			);
		}
	}

}
