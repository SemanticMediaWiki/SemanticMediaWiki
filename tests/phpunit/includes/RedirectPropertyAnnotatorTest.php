<?php

namespace SMW\Test;

use SMW\RedirectPropertyAnnotator;
use SMW\DIProperty;
use SMW\SemanticData;

/**
 * Tests for the RedirectPropertyAnnotator class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\RedirectPropertyAnnotator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class RedirectPropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\RedirectPropertyAnnotator';
	}

	/**
	 * Helper method that returns a RedirectPropertyAnnotator object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return RedirectPropertyAnnotator
	 */
	private function getInstance( SemanticData $data = null ) {
		return new RedirectPropertyAnnotator( $data === null ? $this->newMockObject()->getMockSemanticData() : $data );
	}

	/**
	 * @test RedirectPropertyAnnotator::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test RedirectPropertyAnnotator::annotate
	 * @dataProvider redirectsDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $expected
	 */
	public function testBuild( $test, $expected ) {

		$semanticData =  new SemanticData( $this->getSubject() );

		$instance = $this->getInstance( $semanticData );
		$instance->isEnabled( $test['isEnabled'] )->annotate( $test['text'] );

		$this->assertSemanticData( $semanticData, $expected );
	}


	/**
	 * Provides redirects injection sample
	 *
	 * @return array
	 */
	public function redirectsDataProvider() {

		$title = $this->getTitle();

		// #0 Title
		$provider[] = array(
			array( 'text' => $title, 'isEnabled' => true ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':' . $title->getText()
			)
		);

		// #1 Disabled
		$provider[] = array(
			array( 'text' => $title, 'isEnabled' => false ),
			array(
				'propertyCount' => 0,
			)
		);

		// #2 Free text
		$provider[] = array(
			array( 'text' => '#REDIRECT [[:Lala]]', 'isEnabled' => true ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':Lala'
			)
		);

		// #3 Free text
		$provider[] = array(
			array( 'text' => '#REDIRECT [[Lala]]', 'isEnabled' => true ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':Lala'
			)
		);

		// #4 Disabled free text
		$provider[] = array(
			array( 'text' => '#REDIRECT [[:Lala]]', 'isEnabled' => false ),
			array(
				'propertyCount' => 0,
			)
		);

		// #5 Invalid free text
		$provider[] = array(
			array( 'text' => '#REDIR [[:Lala]]', 'isEnabled' => true ),
			array(
				'propertyCount' => 0,
			)
		);

		// #6 Empty
		$provider[] = array(
			array( 'text' => '', 'isEnabled' => true ),
			array(
				'propertyCount' => 0,
			)
		);

		return $provider;
	}

}
