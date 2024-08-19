<?php

namespace SMW\Tests\Integration;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Exporter\ExporterFactory;
use SMWQuery as Query;
use Title;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class InterwikiDBIntegrationTest extends SMWIntegrationTestCase {

	private $stringValidator;
	private $subjects = [];

	private $stringBuilder;

	private $queryResultValidator;
	private $queryParser;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
		$this->stringBuilder = $utilityFactory->newStringBuilder();

		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();
		$this->queryParser = ApplicationFactory::getInstance()->newQueryParser();

		$utilityFactory->newMwHooksHandler()
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		// Manipulate the interwiki prefix on-the-fly
		$GLOBALS['wgHooks']['InterwikiLoadPrefix'][] = function ( $prefix, &$interwiki ) {
			if ( $prefix !== 'iw-test' ) {
				return true;
			}

			$interwiki = [
				'iw_prefix' => 'iw-test',
				'iw_url' => 'http://www.example.org/$1',
				'iw_api' => false,
				'iw_wikiid' => 'foo',
				'iw_local' => true,
				'iw_trans' => false,
			];

			return false;
		};
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wgHooks']['InterwikiLoadPrefix'] );

		parent::tearDown();
	}

	public function testRdfSerializationForInterwikiAnnotation() {
		if ( version_compare( MW_VERSION, '1.40', '>=' ) ) {
			$this->markTestSkipped( 'The Serialization for interwiki needs to be checked for MW 1.40 and newer.' );
		}

		$titleOne = Title::newFromText( 'Use for interwiki annotation', SMW_NS_PROPERTY );
		$wikiPageOne = parent::getNonexistingTestPage( $titleOne );
		parent::editPage( $wikiPageOne, '[[Has type::Page]]' );

		$titleTwo = Title::newFromText( __METHOD__ );
		$wikiPageTwo = parent::getNonexistingTestPage( $titleTwo );

		$this->stringBuilder
			->addString( '[[Use for interwiki annotation::Interwiki link]]' )
			->addString( '[[Use for interwiki annotation::iw-test:Interwiki link]]' );

		parent::editPage( $wikiPageTwo, $this->stringBuilder->getString() );

		$output = $this->fetchSerializedRdfOutputFor(
			[ __METHOD__ ]
		);

		$expectedOutputContent = [
			'<property:Use_for_interwiki_annotation rdf:resource="&wiki;Interwiki_link"/>',
			'<property:Use_for_interwiki_annotation rdf:resource="&wiki;iw-2Dtest-3AInterwiki_link"/>'
		];

		$this->stringValidator->assertThatStringContains(
			$expectedOutputContent,
			$output
		);
	}

	public function testQueryForInterwikiAnnotation() {
		$titleOne = Title::newFromText( __METHOD__ . '-1' );
		$wikiPageOne = parent::getNonexistingTestPage( $titleOne );
		parent::editPage( $wikiPageOne, '[[Use for interwiki annotation::Interwiki link]]' );

		$titleTwo = Title::newFromText( __METHOD__ . '-2' );
		$wikiPageTwo = parent::getNonexistingTestPage( $titleTwo );
		parent::editPage( $wikiPageTwo, '[[Use for interwiki annotation::iw-test:Interwiki link]]' );

		$this->stringBuilder
			->addString( '[[Use for interwiki annotation::iw-test:Interwiki link]]' );

		$description = $this->queryParser->getQueryDescription( $this->stringBuilder->getString() );

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;
		$query->setLimit( 10 );

		// Expects only one result with an interwiki being used as differentiator
		$this->subjects[] = DIWikiPage::newFromTitle( $titleTwo );
		$queryResult = $this->getStore()->getQueryResult( $query );
	
		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->subjects,
			$queryResult
		);

		$this->subjects[] = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ . '-1' ) );
	}

	private function fetchSerializedRdfOutputFor( array $pages ) {
		$this->subjects = $pages;
		$exporterFactory = new ExporterFactory();

		$instance = $exporterFactory->newExportController(
			$exporterFactory->newRDFXMLSerializer()
		);

		ob_start();
		$instance->printPages( $pages );
		return ob_get_clean();
	}
}
