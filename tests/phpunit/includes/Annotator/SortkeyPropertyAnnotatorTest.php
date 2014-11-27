<?php

namespace SMW\Tests\Annotator;

use SMW\Tests\Utils\Validators\SemanticDataValidator;
use SMW\Tests\Utils\SemanticDataFactory;

use SMW\Annotator\SortkeyPropertyAnnotator;
use SMW\Annotator\NullPropertyAnnotator;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Annotator\SortkeyPropertyAnnotator
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SortkeyPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = new SemanticDataFactory();
		$this->semanticDataValidator = new SemanticDataValidator();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SortkeyPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			'Foo'
		);

		$this->assertInstanceOf(
			'\SMW\Annotator\SortkeyPropertyAnnotator',
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

		$instance = new SortkeyPropertyAnnotator(
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
