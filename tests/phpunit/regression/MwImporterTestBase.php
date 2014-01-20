<?php

namespace SMW\Test;

use SMW\SemanticData;
use SMW\ParserData;
use SMW\StoreFactory;
use SMW\DIWikiPage;

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

	function run( \PHPUnit_Framework_TestResult $result = null ) {

		// Only where teardownTestDB is available (excludes 1.19/1.20), we are
		// able to rebuild the DB (exclude temporary table usage) otherwise
		// some tests will fail with "Error: 1137 Can't reopen table" on Mysql
		if ( method_exists( $this, 'teardownTestDB' ) ) {
			$this->enabledDB = true;
			$this->teardownTestDB();
			$this->setCliArg( 'use-normal-tables', true );
		}

		parent::run( $result );
	}

	public abstract function getSourceFile();

	public abstract function getTitles();

	public abstract function assertSemanticData();

	public function testDataImport() {

		$this->assertTitleIsNotKnownBeforeImport( $this->getTitles() );

		$importer = new MwImporter( $this->getSourceFile(), true );

		$result = $importer->run();

		if ( !$result->isGood() ) {
			$importer->reportFailedImport();
		}

		if ( $this->isEnabledDatabase() ) {
			$this->assertTitleIsKnownAfterImport( $this->getTitles() );
			$this->assertSemanticData();
		}

	}

	/**
	 * @see PHPUnit_Util_Test::parseAnnotations
	 */
	public function parseAnnotations( Title $title ) {

		$annotations = array();

		$docblock = $this->fetchOutput( $title )->getText();

		if ( preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $docblock, $matches ) ) {
			$numMatches = count($matches[0]);

			for ( $i = 0; $i < $numMatches; ++$i ) {
				$annotations[$matches['name'][$i]][] = $matches['value'][$i];
			}
		}

		return $annotations;
	  }

	protected function assertTitleIsNotKnownBeforeImport( $titles ) {
		$this->assertTitleExists( false, $titles );
	}

	protected function assertTitleIsKnownAfterImport( $titles ) {
		$this->assertTitleExists( true, $titles );
	}

	protected function fetchSemanticDataFromStore( Title $title ) {
		return StoreFactory::getStore()->getSemanticData( DIWikiPage::newFromTitle( $title ) );
	}

	protected function fetchOutput( Title $title ) {

		$wikiPage = WikiPage::factory( $title );
		$revision = $wikiPage->getRevision();

		$parserOutput = $wikiPage->getParserOutput(
			$wikiPage->makeParserOptions( User::newFromId( $revision->getUser() ) ),
			$revision->getId()
		);

		return $parserOutput;
	}

	protected function fetchSemanticDataFromOutput( Title $title ) {
		$parserData = new ParserData( $title, $this->fetchOutput( $title ) );
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

	protected function isEnabledDatabase() {

		if ( !$this->enabledDB ) {
			$this->markTestSkipped(
				'MySQL DB setup did not satisfy the test requirements (probably MW 1.19/1.20)'
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
