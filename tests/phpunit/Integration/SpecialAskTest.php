<?php

namespace SMW\Tests\Integration;

use DOMDocument;
use SMWAskPage;

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

	/**
	 * @dataProvider provideTestData
	 * @param $params
	 */
	public function testProducesWellformedHtml( $params ) {

		$this->setupGlobals( $params );

		$special = new SMWAskPage();
		$special->execute( null );

		$html = $GLOBALS['wgOut']->getHtml();
		$html = '<!DOCTYPE html><html><body>' . $html . '</body></html>';

		$document = new DOMDocument();
		$result = $document->loadHTML( $html );
		$this->assertTrue( $result );

		$result = $document->loadXML( $html );
		$this->assertTrue( $result );

		$this->restoreGlobals( $params );
	}

	/**
	 * @return array
	 */
	public function provideTestData() {
		return array(
			array( array( 'eq' => 'yes', 'q' => '' ) ),
			array( array( 'eq' => 'no', 'q' => '[[]]' ) ),
		);
	}

	/**
	 * @param array $params
	 */
	protected function setupGlobals( $params ) {
		global $wgOut, $wgRequest;

		$this->oldRequestValues = array();

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
