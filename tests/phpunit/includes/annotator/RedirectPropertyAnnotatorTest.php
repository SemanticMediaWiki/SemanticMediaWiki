<?php

namespace SMW\Test;

use SMW\DIWikiPage;
use SMW\EmptyContext;
use SMW\NullPropertyAnnotator;
use SMW\RedirectPropertyAnnotator;
use SMW\SemanticData;

/**
 * @covers \SMW\RedirectPropertyAnnotator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RedirectPropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\RedirectPropertyAnnotator';
	}

	/**
	 * @since 1.9
	 *
	 * @return RedirectPropertyAnnotator
	 */
	private function newInstance( $semanticData = null, $text = '' ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		$context  = new EmptyContext();

		return new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, $context ),
			$text
		);

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider redirectsDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddCategoriesWithOutObserver( array $setup, array $expected ) {

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( $this->newTitle() )
		);

		$instance = $this->newInstance( $semanticData, $setup['text'] );
		$instance->addAnnotation();

		$this->assertSemanticData(
			$instance->getSemanticData(),
			$expected,
			'Asserts that addAnnotation() adds expected triples'
		);

	}

	/**
	 * @return array
	 */
	public function redirectsDataProvider() {

		// #0 Free text
		$provider[] = array(
			array( 'text' => '#REDIRECT [[:Lala]]' ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':Lala'
			)
		);

		// #1 Free text
		$provider[] = array(
			array( 'text' => '#REDIRECT [[Lala]]' ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':Lala'
			)
		);


		// #2 Invalid free text
		$provider[] = array(
			array( 'text' => '#REDIR [[:Lala]]' ),
			array(
				'propertyCount' => 0,
			)
		);

		// #3 Empty
		$provider[] = array(
			array( 'text' => '' ),
			array(
				'propertyCount' => 0,
			)
		);

		return $provider;
	}

}
