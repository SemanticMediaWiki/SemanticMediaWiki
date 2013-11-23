<?php

namespace SMW\Test;

use ImportReporter;
use ImportStreamSource;
use MWException;
use RequestContext;
use SMW\ParserData;

use SMw\SemanticData;
use Title;
use User;
use WikiImporter;
use WikiPage;

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

	/**
	 * @dataProvider importProvider
	 *
	 * @since 1.9
	 */
	public function testImportRunner( $setup, $expected ) {

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

		// FIXME Apparently accessing the Store using the DB unit test table
		// will cause:
		//
		// DBQueryError: A database error has occurred.
		// Function: SMW::getSemanticData
		// Error: 1137 Can't reopen table: 'p' (localhost)
		//
		// We suspend running this particular test until it is clear what
		// is causing this issue
		// SMW_SQLStore3_Readers.php:328 is causing the error

		// $this->assertPropertiesAreSet(
		//	$this->fetchSemanticDataFromOutput( $title ),
		//	$expected
		// );

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
