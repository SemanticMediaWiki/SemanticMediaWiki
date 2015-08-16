<?php

namespace SMW\Tests\PropertyAnnotator;

use SMW\Tests\Utils\UtilityFactory;
use SMW\PropertyAnnotator\SortKeyPropertyAnnotator;
use SMW\PropertyAnnotator\NullPropertyAnnotator;
use SMW\DIWikiPage;

/**
 * @covers \SMW\PropertyAnnotator\SortKeyPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SortKeyPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

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

		$instance = new SortKeyPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			'Foo'
		);

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\SortKeyPropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider defaultSortDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddDefaultSortOnMockObserver( array $parameters, array $expected ) {

		$semanticData = $this->semanticDataFactory->setTitle( $parameters['title'] )->newEmptySemanticData();

		$instance = new SortKeyPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$parameters['sort']
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function defaultSortDataProvider() {

		$provider = array();

		// Sort entry
		$provider[] = array(
			array(
				'title' => 'Foo',
				'sort'  => 'Lala'
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => array( 'Lala' ),
			)
		);

		// Empty
		$provider[] = array(
			array(
				'title' => 'Bar',
				'sort'  => ''
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_SKEY',
				'propertyValues' => array( 'Bar' ),
			)
		);

		return $provider;
	}

}
