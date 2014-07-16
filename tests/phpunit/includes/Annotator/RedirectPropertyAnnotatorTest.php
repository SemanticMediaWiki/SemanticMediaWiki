<?php

namespace SMW\Tests\Annotator;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\SemanticDataFactory;

use SMW\Annotator\RedirectPropertyAnnotator;
use SMW\Annotator\NullPropertyAnnotator;
use SMW\MediaWiki\RedirectTargetFinder;

/**
 * @covers \SMW\Annotator\RedirectPropertyAnnotator
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

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = new SemanticDataFactory();
		$this->semanticDataValidator = new SemanticDataValidator();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( '\SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$redirectTargetFinder
		);

		$this->assertInstanceOf(
			'\SMW\Annotator\RedirectPropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider redirectsDataProvider
	 */
	public function testAddAnnotation( array $parameter, array $expected ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new RedirectPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$redirectTargetFinder->findTarget( $parameter['text'] )
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

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
