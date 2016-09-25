<?php

namespace SMW\Tests\PropertyAnnotators;

use SMW\MediaWiki\RedirectTargetFinder;
use SMW\PropertyAnnotators\NullPropertyAnnotator;
use SMW\PropertyAnnotators\RedirectPropertyAnnotator;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\PropertyAnnotators\RedirectPropertyAnnotator
 * @group semantic-mediawiki
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

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
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
			'\SMW\PropertyAnnotators\RedirectPropertyAnnotator',
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
			$redirectTargetFinder->findRedirectTargetFromText( $parameter['text'] )
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
