<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DIWikiPage;
use SMW\Property\Annotators\DisplayTitlePropertyAnnotator;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Property\Annotators\DisplayTitlePropertyAnnotator
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
			'\SMW\Property\Annotators\DisplayTitlePropertyAnnotator',
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

		$expected = [
			'propertyCount'  => 2,
			'propertyKeys'   => [ '_DTITLE', '_SKEY' ],
			'propertyValues' => [ 'Bar' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testNoAnnotationWhenDisabled() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData(
			DIWikiPage::newFromText( 'Foo' )
		);

		$instance = new DisplayTitlePropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			'Bar'
		);
		$instance->canCreateAnnotation( false );
		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 0
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function displayTitleProvider() {

		$provider = [];

		#0 with title entry
		$provider[] = [
			'Foo',
			'Lala',
			'',
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_DTITLE', '_SKEY' ],
				'propertyValues' => [ 'Lala' ],
			]
		];

		#1 Empty
		$provider[] = [
			'Bar',
			'',
			'',
			[
				'propertyCount'  => 0,
				'propertyKeys'   => '',
				'propertyValues' => [],
			]
		];

		#2 Empty
		$provider[] = [
			'Bar',
			false,
			'',
			[
				'propertyCount'  => 0,
				'propertyKeys'   => '',
				'propertyValues' => [],
			]
		];

		#3 Strip tags
		$provider[] = [
			'Bar',
			'<span style="position: absolute; clip: rect(1px 1px 1px 1px); clip: rect(1px, 1px, 1px, 1px);">FOO</span>',
			'',
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_DTITLE', '_SKEY' ],
				'propertyValues' => [ 'FOO' ],
			]
		];


		#4 Strip tags
		$provider[] = [
			'Foo',
			"A 'quote' is <b>bold</b>",
			'',
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_DTITLE', '_SKEY' ],
				'propertyValues' => [ "A 'quote' is bold" ],
			]
		];

		#5 with different sortkey
		$provider[] = [
			'Foo',
			'Lala',
			'BAR',
			[
				'propertyCount'  => 1,
				'propertyKeys'   => [ '_DTITLE' ],
				'propertyValues' => [ 'Lala' ],
			]
		];

		#6 unencoded Html entity
		$provider[] = [
			'Foo',
			'ABC & DEF',
			'',
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_DTITLE', '_SKEY' ],
				'propertyValues' => [ 'ABC & DEF' ],
			]
		];

		#7 decoded/encoded Html entity
		$provider[] = [
			'Foo',
			'ABC &amp; DEF',
			'',
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_DTITLE', '_SKEY' ],
				'propertyValues' => [ 'ABC & DEF' ],
			]
		];

		#8 decoded/encoded ' (&#39;) entity
		$provider[] = [
			'Foo',
			'ABC &#39; DEF',
			'',
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_DTITLE', '_SKEY' ],
				'propertyValues' => [ "ABC ' DEF" ],
			]
		];

		return $provider;
	}

}
