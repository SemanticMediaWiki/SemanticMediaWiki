<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\ParserFunctions\SubobjectParserFunction;
use SMW\ParserParameterFormatter;
use SMW\Subobject;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\PHPUnitCompat;
use Title;

/**
 * @covers \SMW\ParserFunctions\SubobjectParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SubobjectParserFunctionTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\SubobjectParserFunction',
			new SubobjectParserFunction(
				$parserData,
				$subobject,
				$messageFormatter
			)
		);
	}

	/**
	 * @dataProvider parameterDataProvider
	 */
	public function testParse( array $parameters, array $expected ) {

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );
		$result   = $instance->parse( new ParserParameterFormatter( $parameters ) );

		$this->assertInternalType(
			'string',
			$result
		);
	}

	/**
	 * @dataProvider parameterDataProvider
	 */
	public function testInstantiatedSubobject( array $parameters, array $expected ) {

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );
		$instance->parse( new ParserParameterFormatter( $parameters ) );

		$this->assertContains(
			$expected['identifier'],
			$subobject->getSubobjectId()
		);
	}

	/**
	 * @dataProvider firstElementDataProvider
	 */
	public function testFirstElementAsPropertyLabel( $isEnabled , array $parameters, array $expected ) {

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__ );
		$subobject    = new Subobject( $title );

		$instance = $this->acquireInstance( $subobject, $parserOutput );
		$instance->useFirstElementAsPropertyLabel( $isEnabled  );

		$instance->parse( new ParserParameterFormatter( $parameters ) );

		// If it is enabled only check for the first character {0} that should
		// contain '_' as the rest is going to be an unknown hash value
		$id = $subobject->getSubobjectId();
		$this->assertEquals( $expected['identifier'], $isEnabled ? $id[0] : $id );

		$parserData = new ParserData( $title, $parserOutput );

		// Add generated title text as property value due to the auto reference
		// setting
		$expected['propertyValues'][] = $title->getText();

		foreach ( $parserData->getSemanticData()->getSubSemanticData() as $containerSemanticData ){
			$this->semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$containerSemanticData
			);
		}
	}

	/**
	 * @dataProvider parameterDataProvider
	 */
	public function testInstantiatedPropertyValues( array $parameters, array $expected ) {
		$this->setupInstanceAndAssertSemanticData(
			$parameters,
			$expected
		);
	}

	/**
	 * @dataProvider tokuFixedParameterProvider
	 */
	public function testSortKeyAnnotation( array $parameters, array $expected ) {
		$this->setupInstanceAndAssertSemanticData(
			$parameters,
			$expected
		);
	}

	public function testSubobjectIdStabilityForFixedSetOfParameters() {

		$parameters = [
			'Foo=Bar'
		];

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );
		$instance->parse( new ParserParameterFormatter( $parameters ) );

		// Expected to be stable for PHP and HHVM as well
		$this->assertEquals(
			'_be96d37a4d7c35be8673cb4229b8fdec',
			$subobject->getSubobjectId()
		);
	}

	public function testParametersOnBeingSorted() {

		$parameters = [
			'Foo=Foobar, Bar',
			'+sep=,'
		];

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );

		$instance->isComparableContent(
			true
		);

		$instance->parse(
			new ParserParameterFormatter( $parameters )
		);

		$this->assertEquals(
			'_c0bea380739b21578cdcf28ed5d4cfd3',
			$subobject->getSubobjectId()
		);
	}

	public function testParametersOnBeingSortedWithRevertedValueOrderProducesSameHash() {

		$parameters = [
			'Foo=Bar, Foobar',
			'+sep=,'
		];

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );

		$instance->isComparableContent(
			true
		);

		$instance->parse(
			new ParserParameterFormatter( $parameters )
		);

		$this->assertEquals(
			'_c0bea380739b21578cdcf28ed5d4cfd3',
			$subobject->getSubobjectId()
		);
	}

	public function testParametersIsNotSorted() {

		$parameters = [
			'Foo=Foobar, Bar',
			'+sep=,'
		];

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );

		$instance->isComparableContent(
			false
		);

		$instance->parse(
			new ParserParameterFormatter( $parameters )
		);

		// Expected to be stable for PHP and HHVM as well
		$this->assertEquals(
			'_ec7323184d89fe1409b5cfaf09950a95',
			$subobject->getSubobjectId()
		);
	}

	public function testCreateSameIdForNormalizedParametersWithEnabledCapitalLinks() {

		$title = Title::newFromText( __METHOD__ );

		$parametersOne = [
			'Foo=Bar',
			'Has foo=bar,Foo',
			'+sep=,'
		];

		$parametersTwo = [
			'foo=Bar',
			'has foo=Foo,bar',
			'+sep=,'
		];

		$subobject = new Subobject( $title );

		$instance = $this->acquireInstance( $subobject );

		$instance->isComparableContent( true );
		$instance->isCapitalLinks( true );

		$instance->parse(
			new ParserParameterFormatter( $parametersOne )
		);

		$id = $subobject->getSubobjectId();

		$instance->parse(
			new ParserParameterFormatter( $parametersTwo )
		);

		$this->assertEquals(
			$id,
			$subobject->getSubobjectId()
		);
	}

	public function testRestrictionOnTooShortFirstPartWhenDotIsUsedForUserNamedSubobject() {

		// #1299, #1302
		// Has dot restriction
		// {{#subobject:foo.bar
		// |Date=Foo
		// }}
		$parameters = [
			'foo.bar',
			'Date=Foo'
		];

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );
		$instance->parse( new ParserParameterFormatter( $parameters ) );

		$this->setExpectedException( '\SMW\Exception\SubSemanticDataException' );
		$subobject->getSubobjectId();
	}

	protected function setupInstanceAndAssertSemanticData( array $parameters, array $expected ) {

		$title = isset( $expected['embeddedTitle'] ) ? $expected['embeddedTitle'] : __METHOD__;

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( $title );
		$subobject    = new Subobject( $title );

		$instance = $this->acquireInstance(
			$subobject,
			$parserOutput
		);

		$instance->parse( new ParserParameterFormatter( $parameters ) );

		$parserData = new ParserData(
			$title,
			$parserOutput
		);

		$subSemanticData = $parserData->getSemanticData()->getSubSemanticData();

		if ( $expected['propertyCount'] == 0 ) {
			$this->assertEmpty(
				$subSemanticData
			);
		}

		foreach ( $subSemanticData as $key => $semanticData ){

			if ( strpos( $semanticData->getSubject()->getSubobjectName(), '_ERR' ) !== false ) {
				continue;
			}

			$this->semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$semanticData
			);
		}
	}

	public function parameterDataProvider() {

		$helpNS = Localizer::getInstance()->getNamespaceTextById( NS_HELP );

		$provider = [];

		#0 Anonymous identifier
		// {{#subobject:
		// |Foo=bar
		// }}
		$provider[] = [
			[ '', 'Foo=bar' ],
			[
				'hasErrors' => false,
				'identifier' => '_',
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			]
		];

		#1 Anonymous identifier
		// {{#subobject:-
		// |Foo=1001 9009
		// }}
		$provider[] = [
			[ '-', 'Foo=1001 9009' ],
			[
				'hasErrors' => false,
				'identifier' => '_',
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => '1001 9009'
			]
		];

		#2 Named identifier
		// {{#subobject:FooBar
		// |FooBar=Bar foo
		// }}
		$provider[] = [
			[ 'FooBar', 'FooBar=Bar foo' ],
			[
				'hasErrors' => false,
				'identifier' => 'FooBar',
				'propertyCount'  => 1,
				'propertyLabels' => 'FooBar',
				'propertyValues' => 'Bar foo'
			]
		];

		#3 Named identifier
		// {{#subobject:Foo bar
		// |Foo=Help:Bar
		// }}
		$provider[] = [
			[ 'Foo bar', 'Foo=Help:Bar' ],
			[
				'hasErrors' => false,
				'identifier' => 'Foo_bar',
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => "$helpNS:Bar"
			]
		];

		#4 Named identifier
		// {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// }}
		$provider[] = [
			[ ' Foo bar foo ', 'Bar=foo Bar' ],
			[
				'hasErrors' => false,
				'identifier' => 'Foo_bar_foo',
				'propertyCount'  => 1,
				'propertyLabels' => 'Bar',
				'propertyValues' => 'Foo Bar'
			]
		];

		#5 Named identifier
		// {{#subobject: Foo bar foo
		// |状況=超やばい
		// |Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject
		// }}
		$provider[] = [
			[
				' Foo bar foo ',
				'状況=超やばい',
				'Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject' ],
			[
				'hasErrors' => false,
				'identifier' => 'Foo_bar_foo',
				'propertyCount'  => 2,
				'propertyLabels' => [ '状況', 'Bar' ],
				'propertyValues' => [ '超やばい', 'Http://www.semantic-mediawiki.org/w/index.php?title=Subobject' ]
			]
		];

		#6 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |Modification date=foo Bar
		// }}
		$provider[] = [
			[ ' Foo bar foo ', 'Bar=foo Bar', 'Modification date=foo Bar' ],
			[
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 2,
				'propertyKeys'   => [ 'Bar', '_ERRC' ],
				'propertyValues' => [ 'Foo Bar' ]
			]
		];

		#7 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |-Foo=foo Bar
		// }}
		$provider[] = [
			[ ' Foo bar foo ', 'Bar=foo Bar', '-Foo=foo Bar' ],
			[
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 2,
				'propertyKeys' => [ 'Bar', '_ERRC' ],
				'propertyValues' => [ 'Foo Bar' ]
			]
		];

		// An empty subobject is not being classified as valid (to create an object)
		#8 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |Modification date=1 Jan 1970
		// }}
		$provider[] = [
			[ ' Foo bar foo ', 'Modification date=1 Jan 1970' ],
			[
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 1,
				'propertyKeys' => [ '_ERRC' ]
			]
		];

		// Get the right language for an error object
		$diPropertyError = new DIProperty( DIProperty::TYPE_ERROR );

		#9 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |Date=Foo
		// }}
		$provider[] = [
			[ ' Foo bar foo ', 'Date=Foo' ],
			[
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 1,
				'propertyKeys' => [ '_ERRC' ]
			]
		];

		// Not dot restriction
		#10 {{#subobject:foobar.bar
		// |Bar=foo Bar
		// }}
		$provider[] = [
			[ 'foobar.bar', 'Bar=foo Bar' ],
			[
				'hasErrors' => false,
				'identifier' => 'foobar.bar',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 1,
				'propertyKeys' => [ 'Bar' ],
				'propertyValues' => [ 'Foo Bar' ]
			]
		];

		return $provider;
	}

	public function firstElementDataProvider() {

		$provider = [];

		// #0 / asserting that a named identifier was turned into an anonymous id
		// {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// }}
		$provider[] = [
			true,
			[ ' Foo bar foo ', 'Bar=foo Bar' ],
			[
				'hasErrors' => false,
				'identifier' => '_',
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Bar', 'Foo bar foo' ],
				'propertyValues' => [ 'Foo Bar' ] // additional value is added during runtime
			]
		];

		// #1 / asserting the validity of the named identifier
		// {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// }}
		$provider[] = [
			false,
			[ ' Foo bar foo ', 'Bar=foo Bar' ],
			[
				'hasErrors' => false,
				'identifier' => 'Foo_bar_foo',
				'propertyCount'  => 1,
				'propertyLabels' => [ 'Bar' ],
				'propertyValues' => [ 'Foo Bar' ]
			]
		];

		return $provider;
	}

	public function tokuFixedParameterProvider() {

		$provider = [];

		// #0 @sortkey
		// {{#subobject:
		// |Bar=foo Bar
		// |@sortkey=9999
		// }}
		$provider[] = [
			[
				'Bar=foo Bar',
				'@sortkey=9999'
			],
			[
				'propertyCount'  => 2,
				'properties'     => [
					new DIProperty( 'Bar' ),
					new DIProperty( '_SKEY' )
				],
				'propertyValues' => [
					'Foo Bar',
					'9999'
				]
			]
		];

		// #1 @sortkey being empty
		// {{#subobject:
		// |Bar=foo Bar
		// |@sortkey=
		// }}
		$provider[] = [
			[
				'Bar=foo Bar',
				'@sortkey='
			],
			[
				'propertyCount'  => 1,
				'properties'     => [
					new DIProperty( 'Bar' )
				],
				'propertyValues' => [
					'Foo Bar'
				]
			]
		];

		// #2 @category
		// {{#subobject:
		// |Bar=foo Bar
		// |@category=1001
		// }}
		$provider[] = [
			[
				'Bar=foo Bar',
				'@category=1001'
			],
			[
				'propertyCount'  => 2,
				'properties'     => [
					new DIProperty( 'Bar' ),
					new DIProperty( '_INST' )
				],
				'propertyValues' => [
					'Foo Bar',
					'1001'
				]
			]
		];

		// #3 @category empty
		// {{#subobject:
		// |Bar=foo Bar
		// |@category=
		// }}
		$provider[] = [
			[
				'Bar=foo Bar',
				'@category='
			],
			[
				'propertyCount'  => 1,
				'properties'     => [
					new DIProperty( 'Bar' )
				],
				'propertyValues' => [
					'Foo Bar'
				]
			]
		];

		// #4 display title to set sortkey
		// {{#subobject:
		// |Display title of=Foo
		// }}
		$provider[] = [
			[
				'Display title of=Foo'
			],
			[
				'propertyCount'  => 2,
				'properties'     => [
					new DIProperty( '_DTITLE' ),
					new DIProperty( '_SKEY' )
				],
				'propertyValues' => [
					'Foo'
				]
			]
		];

		// #4 display title to set sortkey
		// {{#subobject:
		// |Display title of=Foo
		// |@sortkey=Bar
		// }}
		$provider[] = [
			[
				'Display title of=Foo',
				'@sortkey=Bar'
			],
			[
				'propertyCount'  => 2,
				'properties'     => [
					new DIProperty( '_DTITLE' ),
					new DIProperty( '_SKEY' )
				],
				'propertyValues' => [
					'Foo',
					'Bar'
				]
			]
		];

		// #5 `Bar` auto-linked with `LinkedToSubjectXY`
		// {{#subobject:
		// |@linkWith=Bar
		// }}
		$provider[] = [
			[
				'@linkWith=Bar'
			],
			[
				'embeddedTitle'  => 'LinkedToSubjectXY',
				'propertyCount'  => 1,
				'properties'     => [
					new DIProperty( 'Bar' )
				],
				'propertyValues' => [
					'LinkedToSubjectXY'
				]
			]
		];

		return $provider;
	}

	/**
	 * @return SubobjectParserFunction
	 */
	private function acquireInstance( Subobject $subobject, ParserOutput $parserOutput = null ) {

		if ( $parserOutput === null ) {
			$parserOutput = new ParserOutput();
		}

		return new SubobjectParserFunction(
			new ParserData( $subobject->getTitle(), $parserOutput ),
			$subobject,
			new MessageFormatter( $subobject->getTitle()->getPageLanguage() )
		);
	}

}
