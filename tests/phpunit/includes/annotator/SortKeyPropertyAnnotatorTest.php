<?php

namespace SMW\Test;

use SMW\SortKeyPropertyAnnotator;
use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;
use SMW\SemanticData;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SortKeyPropertyAnnotator
 * @covers \SMW\PropertyAnnotatorDecorator
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
class SortKeyPropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SortKeyPropertyAnnotator';
	}

	/**
	 * @since 1.9
	 *
	 * @return Observer
	 */
	private function newObserver() {

		return $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'updateOutputCallback' )
		) );

	}

	/**
	 * @since 1.9
	 *
	 * @return SortKeyPropertyAnnotator
	 */
	private function newInstance( $semanticData = null, $sortkey = null ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		return new SortKeyPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, new EmptyContext() ),
			$sortkey
		);

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider defaultSortDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddDefaultSortOnMockObserver( array $setup, array $expected ) {

		$semanticData = new SemanticData( DIWikiPage::newFromTitle( $setup['title'] ) );

		$instance = $this->newInstance( $semanticData, $setup['sort'] );
		$instance->attach( $this->newObserver() )->addAnnotation();

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance->getSemanticData() );

		$this->assertEquals(
			$instance->verifyCallback,
			'updateOutputCallback',
			'Asserts that the invoked Observer was notified'
		);

	}

	/**
	 * Verify that the Observer is reachable
	 *
	 * @since 1.9
	 */
	public function updateOutputCallback( $instance ) {

		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );

		return $instance->verifyCallback = 'updateOutputCallback';
	}

	/**
	 * @return array
	 */
	public function defaultSortDataProvider() {

		$provider = array();

		// Sort entry
		$provider[] = array(
			array(
				'title' => $this->newTitle(),
				'sort'  => 'Lala'
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => array( 'Lala' ),
			)
		);

		// Empty
		$title = $this->newTitle();
		$provider[] = array(
			array(
				'title' => $title,
				'sort'  => ''
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => array( $title->getDBkey() ),
			)
		);

		return $provider;
	}

}
