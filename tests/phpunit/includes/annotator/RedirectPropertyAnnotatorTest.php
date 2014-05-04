<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

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
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RedirectPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( 'SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\RedirectPropertyAnnotator',
			new RedirectPropertyAnnotator(
				new NullPropertyAnnotator( $semanticData, new EmptyContext() ),
				$title
			)
		);
	}

	/**
	 * @dataProvider redirectsDataProvider
	 */
	public function testAddAnnotationWithOutObserver( array $parameter, array $expected ) {

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) )
		);

		$instance = new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, new EmptyContext() ),
			Title::newFromRedirect( $parameter['text'] )
		);

		$instance->addAnnotation();

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
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
