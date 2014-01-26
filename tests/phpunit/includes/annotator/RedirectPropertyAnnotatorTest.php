<?php

namespace SMW\Test;

use SMW\RedirectPropertyAnnotator;
use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;
use SMW\SemanticData;
use SMW\DIWikiPage;

use Title;

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

	public function getClass() {
		return '\SMW\RedirectPropertyAnnotator';
	}

	/**
	 * @return RedirectPropertyAnnotator
	 */
	private function newInstance( $semanticData = null, $text = '' ) {

		if ( $semanticData === null ) {
			$semanticData = $this->getMockBuilder( 'SMW\SemanticData' )
				->disableOriginalConstructor()
				->getMock();
		}

		$context  = new EmptyContext();

		return new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, $context ),
			$text
		);

	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider redirectsDataProvider
	 */
	public function testAddAnnotationWithOutObserver( array $parameter, array $expected ) {

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) )
		);

		$instance = $this->newInstance( $semanticData, $parameter['text'] );
		$instance->addAnnotation();

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance->getSemanticData() );

	}

	/**
	 * @dataProvider redirectsDataProvider
	 */
	public function testAddAnnotationWithDisabledContentHandler( $parameter, $expected ) {

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) )
		);

		$instance = $this->getMock( $this->getClass(),
			array( 'hasContentHandler' ),
			array(
				new NullPropertyAnnotator( $semanticData, new EmptyContext() ),
				$parameter['text']
			)
		);

		$instance->expects( $this->any() )
			->method( 'hasContentHandler' )
			->will( $this->returnValue( false ) );

		$instance->addAnnotation();

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance->getSemanticData() );

	}

	/**
	 * @return array
	 */
	public function redirectsDataProvider() {

		// #0 Free text
		$provider[] = array(
			array( 'text' => '#REDIRECT [[:Lala]]' ),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_REDI',
				'propertyValues' => ':Lala'
			)
		);

		// #1 Free text
		$provider[] = array(
			array( 'text' => '#REDIRECT [[Lala]]' ),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_REDI',
				'propertyValues' => ':Lala'
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
