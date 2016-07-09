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
	public function testAddAnnotationForDisplayTitle( $title, $displayTitle, $defaultSort, array $expected ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			$title
		);

		$instance = new DisplayTitlePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$displayTitle,
			$defaultSort
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
			'propertyCount'  => 2,
			'propertyKeys'   => array( '_DTITLE', '_SKEY' ),
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
			'',
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_DTITLE', '_SKEY' ),
				'propertyValues' => array( 'Lala' ),
			)
		);

		#1 Empty
		$provider[] = array(
			'Bar',
			'',
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
			'',
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
			'',
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_DTITLE', '_SKEY' ),
				'propertyValues' => array( 'FOO' ),
			)
		);


		#4 Strip tags
		$provider[] = array(
			'Foo',
			"A 'quote' is <b>bold</b>",
			'',
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_DTITLE', '_SKEY' ),
				'propertyValues' => array( "A 'quote' is bold" ),
			)
		);

		#5 with different sortkey
		$provider[] = array(
			'Foo',
			'Lala',
			'BAR',
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => array( '_DTITLE' ),
				'propertyValues' => array( 'Lala' ),
			)
		);

		#6 unencoded Html entity
		$provider[] = array(
			'Foo',
			'ABC & DEF',
			'',
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_DTITLE', '_SKEY' ),
				'propertyValues' => array( 'ABC & DEF' ),
			)
		);

		#7 decoded/encoded Html entity
		$provider[] = array(
			'Foo',
			'ABC &amp; DEF',
			'',
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_DTITLE', '_SKEY' ),
				'propertyValues' => array( 'ABC & DEF' ),
			)
		);

		#8 decoded/encoded ' (&#39;) entity
		$provider[] = array(
			'Foo',
			'ABC &#39; DEF',
			'',
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_DTITLE', '_SKEY' ),
				'propertyValues' => array( "ABC ' DEF" ),
			)
		);

		return $provider;
	}

}
