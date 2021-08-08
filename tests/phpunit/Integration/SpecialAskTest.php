<?php

namespace SMW\Tests\Integration;

use DOMDocument;
use SMW\MediaWiki\Specials\SpecialAsk;
use SMW\Tests\TestEnvironment;
use SMW\SPARQLStore\RepositoryConnectionProvider;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author Stephan Gambke
 */
class SpecialAskTest extends \PHPUnit_Framework_TestCase {

	private $oldRequestValues;
	private $oldBodyText;
	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgSpecialAskFormSubmitMethod' => SMW_SASK_SUBMIT_GET
			]
		);
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider provideTestData
	 * @param $params
	 * @param $skipFUSEKI
	 */
	public function testProducesWellformedHtml( $params, $skipFUSEKI ) {

		$instance = new RepositoryConnectionProvider( 'fuSEKi' );
		$this->assertInstanceOf(
			'\SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector',
			$instance->getConnection()
		);
		$hasFUSEKI = ( $instance->getConnection()->getVersion() !== "n/a" );

		if ( $hasFUSEKI === true && $skipFUSEKI === true ) {
			$this->markTestSkipped( "FIXME for FUSEKI" );
		}

		$this->setupGlobals( $params );

		$special = new SpecialAsk();
		$special->execute( null );

		$html = $GLOBALS['wgOut']->getHtml();
		$html = '<!DOCTYPE html><html><body>' . $html . '</body></html>';

		// Known tags DOMDocument has issues with
		$html = str_replace( [ '<nowiki>', '</nowiki>' ], '', $html );

		$document = new DOMDocument();

		// https://stackoverflow.com/questions/6090667/php-domdocument-errors-warnings-on-html5-tags
		libxml_use_internal_errors(true);
		$result = $document->loadHTML( $html );
		libxml_clear_errors();

		$this->assertTrue( $result );

		$result = $document->loadXML( $html );
		$this->assertTrue( $result );

		$this->restoreGlobals( $params );
	}

	/**
	 * @return array
	 */
	public function provideTestData() {
		return [
			[ [ [ 'eq' => 'yes', 'q' => '' ] ], false ],
			[ [ [ 'eq' => 'no', 'q' => '[[]]' ] ], true ],
		];
	}

	/**
	 * @param array $params
	 */
	protected function setupGlobals( $params ) {
		global $wgOut, $wgRequest;

		$this->oldRequestValues = [];

		foreach ( $params as $key => $value ) {

			$oldVal = $wgRequest->getText( $key, null );
			if ( $oldVal !== null ) {
				$this->oldRequestValues[$key] = $oldVal;
			}

			$wgRequest->setVal( $key, $value );
		}

		$this->oldBodyText = $wgOut->getHTML();
		$wgOut->clearHTML();
	}

	/**
	 * @param array $params
	 */
	protected function restoreGlobals( $params ) {
		global $wgOut, $wgRequest;

		foreach ( $params as $key => $value ) {
			if ( method_exists( $wgRequest, 'unsetVal' ) ) {
				$wgRequest->unsetVal( $key );
			} else {
				$wgRequest->setVal( $key, null );
			}
		}
		foreach ( $this->oldRequestValues as $key => $value ) {
			$wgRequest->setVal( $key, $value );
		}

		$wgOut->clearHTML();
		$wgOut->addHTML( $this->oldBodyText );
	}
}
