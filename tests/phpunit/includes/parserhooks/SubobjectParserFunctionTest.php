<?php

namespace SMW\Test;

use SMW\SubobjectParserFunction;
use SMW\Subobject;
use SMW\ParserParameterFormatter;
use SMW\MessageFormatter;

use SMWDIProperty;
use SMWDataItem;
use Title;
use ParserOutput;

/**
 * Tests for the SubobjectParserFunction class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SubobjectParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SubobjectParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\SubobjectParserFunction';
	}

	/**
	 * Helper method that returns a SubobjectParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param $title
	 * @param $parserOutput
	 *
	 * @return SubobjectParserFunction
	 */
	private function newInstance( Title $title= null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		return new SubobjectParserFunction(
			$this->newParserData( $title, $parserOutput ),
			new Subobject( $title ),
			new MessageFormatter( $title->getPageLanguage() )
		);
	}

	/**
	 * @test SubobjectParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test SubobjectParserFunction::parse
	 * @dataProvider getSubobjectDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {

		$instance = $this->newInstance( $this->newTitle(), $this->newParserOutput() );
		$result   = $instance->parse( $this->getParserParameterFormatter( $params ) );

		$this->assertEquals( $result !== '' , $expected['errors'] );

	}

	/**
	 * @test SubobjectParserFunction::parse
	 * @dataProvider getSubobjectDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedSubobject( array $params, array $expected ) {

		$instance = $this->newInstance( $this->newTitle(), $this->newParserOutput() );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		$this->assertContains( $expected['identifier'], $instance->getSubobject()->getId() );

	}

	/**
	 * @test SubobjectParserFunction::parse
	 * @dataProvider getObjectReferenceDataProvider
	 *
	 * @since 1.9
	 *
	 * @param boolean $isEnabled
	 * @param array $params
	 * @param array $expected
	 */
	public function testObjectReference( $isEnabled, array $params, array $expected, array $info ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();

		// Initialize and parse
		$instance = $this->newInstance( $title, $parserOutput );
		$instance->setObjectReference( $isEnabled );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		// If it is enabled only check for the first character {0} that should
		// contain '_' as the rest is going to be an unknown hash value
		$id = $instance->getSubobject()->getId();
		$this->assertEquals( $expected['identifier'], $isEnabled ? $id{0} : $id, $info['msg'] );

		// Get data instance
		$parserData = $this->newParserData( $title, $parserOutput );

		// Add generated title text as property value due to the auto reference
		// setting
		$expected['propertyValue'][] = $title->getText();

		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertSemanticData( $containerSemanticData, $expected );
		}
	}

	/**
	 * Test instantiated property and value strings
	 *
	 * @test SubobjectParserFunction::parse
	 * @dataProvider getSubobjectDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedPropertyValues( array $params, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();

		// Initialize and parse
		$instance = $this->newInstance( $title, $parserOutput );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		// Get semantic data from the ParserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( '\SMW\SemanticData', $parserData->getData() );

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertSemanticData( $containerSemanticData, $expected );
		}
	}

	/**
	 * @test SubobjectParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {

		$parser = $this->newParser( $this->newTitle(), $this->getUser() );
		$result = SubobjectParserFunction::render( $parser );

		$this->assertInternalType( 'string', $result );
	}

	/**
	 * Provides data sample normally found in connection with the {{#subobject}}
	 * parser function. The first array contains parametrized input value while
	 * the second array contains expected return results for the instantiated
	 * object.
	 *
	 * @return array
	 */
	public function getSubobjectDataProvider() {
		// Get the right language for an error object
		$diPropertyError = new SMWDIProperty( SMWDIProperty::TYPE_ERROR );

		return array(

			// Anonymous identifier
			// {{#subobject:
			// |Foo=bar
			// }}
			array(
				array( '', 'Foo=bar' ),
				array(
					'errors' => false,
					'identifier' => '_',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => 'Bar'
				)
			),

			// Anonymous identifier
			// {{#subobject:-
			// |Foo=1001 9009
			// }}
			array(
				array( '-', 'Foo=1001 9009' ),
				array(
					'errors' => false,
					'identifier' => '_',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => '1001 9009'
				)
			),

			// Named identifier
			// {{#subobject:FooBar
			// |FooBar=Bar foo
			// }}
			array(
				array( 'FooBar', 'FooBar=Bar foo' ),
				array(
					'errors' => false,
					'identifier' => 'FooBar',
					'propertyCount' => 1,
					'propertyLabel' => 'FooBar',
					'propertyValue' => 'Bar foo'
				)
			),

			// Named identifier
			// {{#subobject:Foo bar
			// |Foo=Help:Bar
			// }}
			array(
				array( 'Foo bar', 'Foo=Help:Bar' ),
				array(
					'errors' => false,
					'identifier' => 'Foo_bar',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => 'Help:Bar'
				)
			),

			// Named identifier
			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// }}
			array(
				array( ' Foo bar foo ', 'Bar=foo Bar' ),
				array(
					'errors' => false,
					'identifier' => 'Foo_bar_foo',
					'propertyCount' => 1,
					'propertyLabel' => 'Bar',
					'propertyValue' => 'Foo Bar'
				)
			),

			// Named identifier
			// {{#subobject: Foo bar foo
			// |状況=超やばい
			// |Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject
			// }}
			array(
				array(
					' Foo bar foo ',
					'状況=超やばい',
					'Bar=http://www.semantic-mediawiki.org/w/index.php?title=Subobject' ),
				array(
					'errors' => false,
					'identifier' => 'Foo_bar_foo',
					'propertyCount' => 2,
					'propertyLabel' => array( '状況', 'Bar' ),
					'propertyValue' => array( '超やばい', 'Http://www.semantic-mediawiki.org/w/index.php?title=Subobject' )
				)
			),

			// Returns an error due to wrong declaration (see Modification date)

			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// |Modification date=foo Bar
			// }}
			array(
				array( ' Foo bar foo ', 'Bar=foo Bar', 'Modification date=foo Bar' ),
				array(
					'errors' => true,
					'identifier' => 'Foo_bar_foo',
					'propertyCount' => 2,
					'propertyLabel' => array( 'Bar', $diPropertyError->getLabel() ),
					'propertyValue' => array( 'Foo Bar', 'Modification date' )
				)
			),
		);
	}

	/**
	 * Subject reference data provider
	 *
	 * @return array
	 */
	public function getObjectReferenceDataProvider() {
		return array(

			// #0
			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// }}
			array(
				true,
				array( ' Foo bar foo ', 'Bar=foo Bar' ),
				array(
					'errors' => false,
					'identifier' => '_',
					'propertyCount' => 2,
					'propertyLabel' => array( 'Bar', 'Foo bar foo' ),
					'propertyValue' => array( 'Foo Bar' ) // additional value is added during runtime
				),
				array( 'msg' => 'Failed asserting that a named identifier was turned into an anonymous id' )
			),

			// #1
			// {{#subobject: Foo bar foo
			// |Bar=foo Bar
			// }}
			array(
				false,
				array( ' Foo bar foo ', 'Bar=foo Bar' ),
				array(
					'errors' => false,
					'identifier' => 'Foo_bar_foo',
					'propertyCount' => 1,
					'propertyLabel' => array( 'Bar' ),
					'propertyValue' => array( 'Foo Bar' )
				),
				array( 'msg' => 'Failed asserting the validity of the named identifier' )
			)
		);
	}
}
