<?php

namespace SMW\Test;

use SMw\SemanticData;
use SMW\ParserData;

use ImportStreamSource;
use ImportReporter;
use WikiImporter;
use MWException;
use RequestContext;
use WikiPage;
use Title;
use User;

/**
 * This test is being used to run a sanity check to verify that necessary
 * components (hooks, extensions etc.) do act according to their specification
 * when content is imported
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PageAnnotationImportSystemTest extends \MediaWikiTestCase {

	protected $enabledAssertOnMySql = false;

	function run( \PHPUnit_Framework_TestResult $result = null ) {

		if( $GLOBALS['wgDBtype'] == 'mysql' ) {

			// Only where teardownTestDB is available (excludes 1.19/1.20), we are
			// able to rebuild the DB (exclude temporary table usage) otherwise
			// some tests will fail with "Error: 1137 Can't reopen table" on mysql
			if ( method_exists( $this, 'teardownTestDB' ) ) {
				$this->enabledOnMySql = true;
				$this->teardownTestDB();
			}

			$this->setCliArg( 'use-normal-tables', true );
		}

		parent::run( $result );
	}

	/**
	 * @dataProvider importProvider
	 *
	 * @since 1.9
	 */
	public function testImportRunner( $setup, $expected ) {

		$this->assertIsEnabled();

		$title = Title::newFromText( $setup['title'] );

		$this->assertFalse(
			$title->exists(),
			'Asserts that the Title does not exist prior the import'
		);

		list( $result, $exception ) = $this->runWikiImporter( $setup['file'] );

		if ( !$result->isGood() ) {
			$this->reportFailedImport( $result, $exception );
		}

		$this->assertTrue(
			$title->exists(),
			'Asserts that the Title does exist after the import'
		);

		$this->assertPropertiesAreSet(
			$this->fetchSemanticDataFromOutput( $title ),
			$expected
		);

	}

	/**
	 * @since 1.9
	 */
	protected function runWikiImporter( $file ) {

		$source = ImportStreamSource::newFromFile( $file );

		$this->assertTrue(
			$source->isGood(),
			'Assert that the file source was available'
		);

		$importer = new WikiImporter( $source->value );

		$reporter = new ImportReporter(
			$importer,
			false,
			'',
			false
		);

		$reporter->setContext( new RequestContext() );
		$exception = false;

		$reporter->open();

		try {
			$importer->doImport();
		} catch ( MWException $e ) {
			$exception = $e;
		}

		$result = $reporter->close();

		return array( $result, $exception );
	}

	/**
	 * @since 1.9
	 */
	protected function reportFailedImport( $result, $exception ) {

		if ( $exception ) {
			var_dump( 'exception: ', $exception->getMessage(), $exception->getTraceAsString() );
		}

		var_dump( 'result: ', $result->getWikiText() );
	}

	/**
	 * @since 1.9
	 */
	protected function fetchSemanticDataFromOutput( Title $title ) {

		$wikiPage = WikiPage::factory( $title );
		$revision = $wikiPage->getRevision();

		$parserOutput = $wikiPage->getParserOutput(
			$wikiPage->makeParserOptions( User::newFromId( $revision->getUser() ) ),
			$revision->getId()
		);

		$parserData = new ParserData( $title, $parserOutput );

		return $parserData->getData();
	}

	protected function assertIsEnabled() {
		if ( !$this->enabledAssertOnMySql ) {
			$this->markTestSkipped(
				'Database could not be rebuild to satisfy test requirements (probably MW 1.19/1.20)'
			);
		}
	}

	/**
	 * @since  1.9
	 */
	protected function assertPropertiesAreSet( SemanticData $semanticData, array $expected ) {

		foreach ( $semanticData->getProperties() as $property ) {

			$this->assertInstanceOf( '\SMW\DIProperty', $property );

			$this->assertContains(
				$property->getKey(),
				$expected['propertyKey'],
				'Asserts that a specific property key is set'
			);

		}
	}

	/**
	 * @return array
	 */
	public function importProvider() {

		$provider = array();

		$provider[] = array(
			array(
				'file'  => __DIR__ . '/' . 'Foo-1-19-7.xml',
				'title' => 'Foo-1-19-7'
			),
			array(
				'propertyKey' => array( 'Foo', 'Quux', '_ASK', '_LEDT', '_MDAT', '_SKEY', '_SOBJ', '_INST' ),
			)
		);

		return $provider;
	}

}
