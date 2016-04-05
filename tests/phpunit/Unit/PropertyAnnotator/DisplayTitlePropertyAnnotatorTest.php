<?php

namespace SMW\Tests\PropertyAnnotator;

use SMW\DIWikiPage;
use SMW\PropertyAnnotator\DisplayTitlePropertyAnnotator;
use SMW\PropertyAnnotator\NullPropertyAnnotator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\PropertyAnnotator\DisplayTitlePropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DisplayTitlePropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$testEnvironment = new TestEnvironment();

		$this->semanticDataFactory = $testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DisplayTitlePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData )
		);

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotator\DisplayTitlePropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider displayTitleProvider
	 */
	public function testAddAnnotationForDisplayTitle( $title, $displayTitle, array $expected ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			$title
		);

		$instance = new DisplayTitlePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$displayTitle
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testAddAnnotationForWhenPropertyNamespaceIsUsed() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY )
		);

		$instance = new DisplayTitlePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			'Bar'
		);

		$instance->addAnnotation();

		$expected = array(
			'propertyCount'  => 1,
			'propertyKeys'   => '_DTITLE',
			'propertyValues' => array( 'Bar' ),
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function displayTitleProvider() {

		$provider = array();

		#0 with title entry
		$provider[] = array(
			'Foo',
			'Lala',
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_DTITLE',
				'propertyValues' => array( 'Lala' ),
			)
		);

		#1 Empty
		$provider[] = array(
			'Bar',
			'',
			array(
				'propertyCount'  => 0,
				'propertyKeys'   => '',
				'propertyValues' => array(),
			)
		);

		#2 Empty
		$provider[] = array(
			'Bar',
			false,
			array(
				'propertyCount'  => 0,
				'propertyKeys'   => '',
				'propertyValues' => array(),
			)
		);

		#3 Strip tags
		$provider[] = array(
			'Bar',
			'<span style="position: absolute; clip: rect(1px 1px 1px 1px); clip: rect(1px, 1px, 1px, 1px);">FOO</span>',
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_DTITLE',
				'propertyValues' => array( 'FOO' ),
			)
		);

		return $provider;
	}

}
