<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\ParserParameterFormatter;
use SMW\Subobject;
use SMW\ParserFunctions\SubobjectParserFunction;
use SMW\Tests\Utils\UtilityFactory;
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
		$this->assertEquals( $expected['identifier'], $isEnabled ? $id{0} : $id );

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

		$parameters = array(
			'Foo=Bar'
		);

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$instance = $this->acquireInstance( $subobject );
		$instance->parse( new ParserParameterFormatter( $parameters ) );

		// Expected to be stable for PHP and HHVM as well
		$this->assertEquals(
			'_be96d37a4d7c35be8673cb4229b8fdec',
			$subobject->getSubobjectId()
		);
	}

	public function testCreateSameIdForNormalizedParametersWithEnabledCapitalLinks() {

		$title = Title::newFromText( __METHOD__ );

		$parametersOne = array(
			'Foo=Bar',
			'Has foo=bar,Foo',
			'+sep=,'
		);

		$parametersTwo = array(
			'foo=Bar',
			'has foo=Foo,bar',
			'+sep=,'
		);

		$subobject = new Subobject( $title );

		$instance = $this->acquireInstance( $subobject );

		$instance->enabledNormalization();
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
		$parameters = array(
			'foo.bar',
			'Date=Foo'
		);

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

		$provider = array();

		#0 Anonymous identifier
		// {{#subobject:
		// |Foo=bar
		// }}
		$provider[] = array(
			array( '', 'Foo=bar' ),
			array(
				'hasErrors' => false,
				'identifier' => '_',
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			)
		);

		#1 Anonymous identifier
		// {{#subobject:-
		// |Foo=1001 9009
		// }}
		$provider[] = array(
			array( '-', 'Foo=1001 9009' ),
			array(
				'hasErrors' => false,
				'identifier' => '_',
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => '1001 9009'
			)
		);

		#2 Named identifier
		// {{#subobject:FooBar
		// |FooBar=Bar foo
		// }}
		$provider[] = array(
			array( 'FooBar', 'FooBar=Bar foo' ),
			array(
				'hasErrors' => false,
				'identifier' => 'FooBar',
				'propertyCount'  => 1,
				'propertyLabels' => 'FooBar',
				'propertyValues' => 'Bar foo'
			)
		);

		#3 Named identifier
		// {{#subobject:Foo bar
		// |Foo=Help:Bar
		// }}
		$provider[] = array(
			array( 'Foo bar', 'Foo=Help:Bar' ),
			array(
				'hasErrors' => false,
				'identifier' => 'Foo_bar',
				'propertyCount'  => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => "$helpNS:Bar"
			)
		);

		#4 Named identifier
		// {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// }}
		$provider[] = array(
			array( ' Foo bar foo ', 'Bar=foo Bar' ),
			array(
				'hasErrors' => false,
				'identifier' => 'Foo_bar_foo',
				'propertyCount'  => 1,
				'propertyLabels' => 'Bar',
				'propertyValues' => 'Foo Bar'
			)
		);

		#5 Named identifier
		// {{#subobject: Foo bar foo
		// |状況=超やばい
		// |Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject
		// }}
		$provider[] = array(
			array(
				' Foo bar foo ',
				'状況=超やばい',
				'Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject' ),
			array(
				'hasErrors' => false,
				'identifier' => 'Foo_bar_foo',
				'propertyCount'  => 2,
				'propertyLabels' => array( '状況', 'Bar' ),
				'propertyValues' => array( '超やばい', 'Http://www.semantic-mediawiki.org/w/index.php?title=Subobject' )
			)
		);

		#6 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |Modification date=foo Bar
		// }}
		$provider[] = array(
			array( ' Foo bar foo ', 'Bar=foo Bar', 'Modification date=foo Bar' ),
			array(
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 2,
				'propertyKeys'   => array( 'Bar', '_ERRC' ),
				'propertyValues' => array( 'Foo Bar' )
			)
		);

		#7 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |-Foo=foo Bar
		// }}
		$provider[] = array(
			array( ' Foo bar foo ', 'Bar=foo Bar', '-Foo=foo Bar' ),
			array(
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 2,
				'propertyKeys' => array( 'Bar', '_ERRC' ),
				'propertyValues' => array( 'Foo Bar' )
			)
		);

		// An empty subobject is not being classified as valid (to create an object)
		#8 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |Modification date=1 Jan 1970
		// }}
		$provider[] = array(
			array( ' Foo bar foo ', 'Modification date=1 Jan 1970' ),
			array(
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 1,
				'propertyKeys' => array( '_ERRC' )
			)
		);

		// Get the right language for an error object
		$diPropertyError = new DIProperty( DIProperty::TYPE_ERROR );

		#9 {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// |Date=Foo
		// }}
		$provider[] = array(
			array( ' Foo bar foo ', 'Date=Foo' ),
			array(
				'hasErrors' => true,
				'identifier' => 'Foo_bar_foo',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 1,
				'propertyKeys' => array( '_ERRC' )
			)
		);

		// Not dot restriction
		#10 {{#subobject:foobar.bar
		// |Bar=foo Bar
		// }}
		$provider[] = array(
			array( 'foobar.bar', 'Bar=foo Bar' ),
			array(
				'hasErrors' => false,
				'identifier' => 'foobar.bar',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 1,
				'propertyKeys' => array( 'Bar' ),
				'propertyValues' => array( 'Foo Bar' )
			)
		);

		return $provider;
	}

	public function firstElementDataProvider() {

		$provider = array();

		// #0 / asserting that a named identifier was turned into an anonymous id
		// {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// }}
		$provider[] = array(
			true,
			array( ' Foo bar foo ', 'Bar=foo Bar' ),
			array(
				'hasErrors' => false,
				'identifier' => '_',
				'propertyCount'  => 2,
				'propertyLabels' => array( 'Bar', 'Foo bar foo' ),
				'propertyValues' => array( 'Foo Bar' ) // additional value is added during runtime
			)
		);

		// #1 / asserting the validity of the named identifier
		// {{#subobject: Foo bar foo
		// |Bar=foo Bar
		// }}
		$provider[] = array(
			false,
			array( ' Foo bar foo ', 'Bar=foo Bar' ),
			array(
				'hasErrors' => false,
				'identifier' => 'Foo_bar_foo',
				'propertyCount'  => 1,
				'propertyLabels' => array( 'Bar' ),
				'propertyValues' => array( 'Foo Bar' )
			)
		);

		return $provider;
	}

	public function tokuFixedParameterProvider() {

		$provider = array();

		// #0 @sortkey
		// {{#subobject:
		// |Bar=foo Bar
		// |@sortkey=9999
		// }}
		$provider[] = array(
			array(
				'Bar=foo Bar',
				'@sortkey=9999'
			),
			array(
				'propertyCount'  => 2,
				'properties'     => array(
					new DIProperty( 'Bar' ),
					new DIProperty( '_SKEY' )
				),
				'propertyValues' => array(
					'Foo Bar',
					'9999'
				)
			)
		);

		// #1 @sortkey being empty
		// {{#subobject:
		// |Bar=foo Bar
		// |@sortkey=
		// }}
		$provider[] = array(
			array(
				'Bar=foo Bar',
				'@sortkey='
			),
			array(
				'propertyCount'  => 1,
				'properties'     => array(
					new DIProperty( 'Bar' )
				),
				'propertyValues' => array(
					'Foo Bar'
				)
			)
		);

		// #2 @category
		// {{#subobject:
		// |Bar=foo Bar
		// |@category=1001
		// }}
		$provider[] = array(
			array(
				'Bar=foo Bar',
				'@category=1001'
			),
			array(
				'propertyCount'  => 2,
				'properties'     => array(
					new DIProperty( 'Bar' ),
					new DIProperty( '_INST' )
				),
				'propertyValues' => array(
					'Foo Bar',
					'1001'
				)
			)
		);

		// #3 @category empty
		// {{#subobject:
		// |Bar=foo Bar
		// |@category=
		// }}
		$provider[] = array(
			array(
				'Bar=foo Bar',
				'@category='
			),
			array(
				'propertyCount'  => 1,
				'properties'     => array(
					new DIProperty( 'Bar' )
				),
				'propertyValues' => array(
					'Foo Bar'
				)
			)
		);

		// #4 display title to set sortkey
		// {{#subobject:
		// |Display title of=Foo
		// }}
		$provider[] = array(
			array(
				'Display title of=Foo'
			),
			array(
				'propertyCount'  => 2,
				'properties'     => array(
					new DIProperty( '_DTITLE' ),
					new DIProperty( '_SKEY' )
				),
				'propertyValues' => array(
					'Foo'
				)
			)
		);

		// #4 display title to set sortkey
		// {{#subobject:
		// |Display title of=Foo
		// |@sortkey=Bar
		// }}
		$provider[] = array(
			array(
				'Display title of=Foo',
				'@sortkey=Bar'
			),
			array(
				'propertyCount'  => 2,
				'properties'     => array(
					new DIProperty( '_DTITLE' ),
					new DIProperty( '_SKEY' )
				),
				'propertyValues' => array(
					'Foo',
					'Bar'
				)
			)
		);

		// #5 `Bar` auto-linked with `LinkedToSubjectXY`
		// {{#subobject:
		// |@linkWith=Bar
		// }}
		$provider[] = array(
			array(
				'@linkWith=Bar'
			),
			array(
				'embeddedTitle'  => 'LinkedToSubjectXY',
				'propertyCount'  => 1,
				'properties'     => array(
					new DIProperty( 'Bar' )
				),
				'propertyValues' => array(
					'LinkedToSubjectXY'
				)
			)
		);

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
