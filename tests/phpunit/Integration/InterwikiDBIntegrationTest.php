<?php

namespace SMW\Tests\Integration;

use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWExportController as ExportController;
use SMWQuery as Query;
use SMWRDFXMLSerializer as RDFXMLSerializer;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class InterwikiDBIntegrationTest extends MwDBaseUnitTestCase {

	private $stringValidator;
	private $subjects = array();

	private $pageCreator;
	private $stringBuilder;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->semanticDataFactory = $utilityFactory->newSemanticDataFactory();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();

		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->stringBuilder = $utilityFactory->newStringBuilder();

		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();
		$this->queryParser = ApplicationFactory::getInstance()->newQueryParser();

		// Manipulate the interwiki prefix on-the-fly
		$GLOBALS['wgHooks']['InterwikiLoadPrefix'][] = function( $prefix, &$interwiki ) {

			if ( $prefix !== 'iw-test' ) {
				return true;
			}

			$interwiki = array(
				'iw_prefix' => 'iw-test',
				'iw_url' => 'http://www.example.org/$1',
				'iw_api' => false,
				'iw_wikiid' => 'foo',
				'iw_local' => true,
				'iw_trans' => false,
			);

			return false;
		};
	}

	protected function tearDown() {

		UtilityFactory::getInstance()->newPageDeleter()->doDeletePoolOfPages( $this->subjects );
		unset( $GLOBALS['wgHooks']['InterwikiLoadPrefix'] );

		parent::tearDown();
	}

	public function testRdfSerializationForInterwikiAnnotation() {

		$this->stringBuilder
			->addString( '[[Has type::Page]]' );

		$this->pageCreator
			->createPage( Title::newFromText( 'Use for interwiki annotation', SMW_NS_PROPERTY ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->stringBuilder
			->addString( '[[Use for interwiki annotation::Interwiki link]]' )
			->addString( '[[Use for interwiki annotation::iw-test:Interwiki link]]' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( $this->stringBuilder->getString() );

		$output = $this->fetchSerializedRdfOutputFor(
			array( __METHOD__ )
		);

		$expectedOutputContent = array(
			'<property:Use_for_interwiki_annotation rdf:resource="&wiki;Interwiki_link"/>',
			'<property:Use_for_interwiki_annotation rdf:resource="&wiki;iw-2Dtest-3AInterwiki_link"/>'
		);

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
		$this->subjects[] = DIWikiPage::newFromTitle(Title::newFromText( __METHOD__ . '-2' ) );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$this->subjects,
			$this->getStore()->getQueryResult( $query )
		);

		$this->subjects[] = DIWikiPage::newFromTitle(Title::newFromText( __METHOD__ . '-1' ) );
	}

	private function fetchSerializedRdfOutputFor( array $pages ) {

		$this->subjects = $pages;

		$instance = new ExportController( new RDFXMLSerializer() );

		ob_start();
		$instance->printPages( $pages );
		return ob_get_clean();
	}

}
