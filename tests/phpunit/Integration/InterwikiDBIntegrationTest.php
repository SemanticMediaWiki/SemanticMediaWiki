<?php

namespace SMW\Tests\Integration;

use MediaWiki\MediaWikiServices;
use SMW\DIWikiPage;
use SMW\Exporter\ExporterFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWQuery as Query;
use Title;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
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
	private $semanticDataFactory;
	private $pageCreator;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->semanticDataFactory = $utilityFactory->newSemanticDataFactory();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
		$this->stringBuilder = $utilityFactory->newStringBuilder();
		$this->pageCreator = $utilityFactory->newPageCreator();

		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();
		$this->queryParser = ApplicationFactory::getInstance()->newQueryParser();

		$utilityFactory->newMwHooksHandler()
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		// Manipulate the interwiki prefix on-the-fly
		MediaWikiServices::getInstance()->getHookContainer()->register(
			'InterwikiLoadPrefix',
			static function ( $prefix, &$interwiki ) {
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
			}
		);
	}

	protected function tearDown(): void {
		MediaWikiServices::getInstance()->getHookContainer()->clear( 'InterwikiLoadPrefix' );

		parent::tearDown();
	}

	public function testRdfSerializationForInterwikiAnnotation() {
		if ( version_compare( MW_VERSION, '1.40', '>=' ) ) {
			$this->markTestSkipped( 'The Serialization for interwiki needs to be checked for MW 1.40 and newer.' );
		}

		$this->stringBuilder
			->addString( '[[Has type::Page]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Use for interwiki annotation', SMW_NS_PROPERTY ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->stringBuilder
			->addString( '[[Use for interwiki annotation::Interwiki link]]' )
			->addString( '[[Use for interwiki annotation::iw-test:Interwiki link]]' );

		// parent::editPage( $wikiPageTwo, $this->stringBuilder->getString() );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( $this->stringBuilder->getString() );

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
		$this->stringBuilder
			->addString( '[[Has type::Page]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Use for interwiki annotation', SMW_NS_PROPERTY ) )
			->doEdit( $this->stringBuilder->getString() );
		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ . '-1' ) )
			->doEdit( '[[Use for interwiki annotation::Interwiki link]]' );
		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ . '-2' ) )
			->doEdit( '[[Use for interwiki annotation::iw-test:Interwiki link]]' );

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
		$this->subjects[] = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ . '-2' ) );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->subjects,
			$this->getStore()->getQueryResult( $query )
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
