<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\SubobjectParserFunction;
use SMW\Subobject;
use SMW\ParserParameterFormatter;
use SMW\MessageFormatter;
use SMW\ParserData;

use SMWDIProperty;
use SMWDataItem;
use Title;
use ParserOutput;

/**
 * @covers \SMW\SubobjectParserFunction
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
class SubobjectParserFunctionTest extends ParserTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\SubobjectParserFunction';
	}

	/**
	 * @since 1.9
	 *
	 * @return SubobjectParserFunction
	 */
	private function newInstance( Subobject $subobject = null, ParserOutput $parserOutput = null ) {

		if ( $subobject === null ) {
			$subobject = new Subobject( $this->newTitle() );
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		return new SubobjectParserFunction(
			$this->newParserData( $subobject->getTitle(), $parserOutput ),
			$subobject,
			new MessageFormatter( $subobject->getTitle()->getPageLanguage() )
		);
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider parameterDataProvider
	 *
	 * @since 1.9
	 */
	public function testParse( array $params, array $expected ) {

		$instance = $this->newInstance();
		$result   = $instance->parse( $this->getParserParameterFormatter( $params ) );

		$this->assertEquals( $result !== '' , $expected['errors'] );

	}

	/**
	 * @dataProvider parameterDataProvider
	 *
	 * @since 1.9
	 */
	public function testInstantiatedSubobject( array $params, array $expected ) {

		$subobject = new Subobject( $this->newTitle() );

		$instance = $this->newInstance( $subobject );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		$this->assertContains( $expected['identifier'], $subobject->getId() );

	}

	/**
	 * @dataProvider firstElementDataProvider
	 */
	public function testFirstElementAsProperty( $isEnabled, array $params, array $expected, array $info ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();
		$subobject    = new Subobject( $title );

		// Initialize and parse
		$instance = $this->newInstance( $subobject, $parserOutput );
		$instance->setFirstElementAsProperty( $isEnabled );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		// If it is enabled only check for the first character {0} that should
		// contain '_' as the rest is going to be an unknown hash value
		$id = $subobject->getId();
		$this->assertEquals( $expected['identifier'], $isEnabled ? $id{0} : $id, $info['msg'] );

		// Get data instance
		$parserData = $this->newParserData( $title, $parserOutput );

		// Add generated title text as property value due to the auto reference
		// setting
		$expected['propertyValues'][] = $title->getText();
		$semanticDataValidator = new SemanticDataValidator;

		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$semanticDataValidator->assertThatPropertiesAreSet( $expected, $containerSemanticData );
		}
	}

	/**
	 * @dataProvider parameterDataProvider
	 *
	 * @since 1.9
	 */
	public function testInstantiatedPropertyValues( array $params, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();
		$subobject    = new Subobject( $title );

		// Initialize and parse
		$instance = $this->newInstance( $subobject, $parserOutput );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		// Get semantic data from the ParserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		$this->assertInstanceOf( '\SMW\SemanticData', $parserData->getData() );
		$semanticDataValidator = new SemanticDataValidator;

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$semanticDataValidator->assertThatPropertiesAreSet( $expected, $containerSemanticData );
		}
	}

	/**
	 * @dataProvider sortKeyProvider
	 */
	public function testSortKeyAnnotation( array $parameters, array $expected ) {

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__ );
		$subobject    = new Subobject( $title );

		$instance = $this->newInstance(
			$subobject,
			$parserOutput
		);

		$instance->parse( new ParserParameterFormatter( $parameters ) );

		$parserData = new ParserData(
			$title,
			$parserOutput
		);

		$subSemanticData = $parserData->getSemanticData()->getSubSemanticData();

		$semanticDataValidator = new SemanticDataValidator();

		foreach ( $subSemanticData as $actualSemanticDataToAssert ){
			$semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$actualSemanticDataToAssert
			);
		}
	}

	/**
	 * @return array
	 */
	public function parameterDataProvider() {
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
					'propertyCount'  => 1,
					'propertyLabels' => 'Foo',
					'propertyValues' => 'Bar'
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
					'propertyCount'  => 1,
					'propertyLabels' => 'Foo',
					'propertyValues' => '1001 9009'
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
					'propertyCount'  => 1,
					'propertyLabels' => 'FooBar',
					'propertyValues' => 'Bar foo'
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
					'propertyCount'  => 1,
					'propertyLabels' => 'Foo',
					'propertyValues' => 'Help:Bar'
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
					'propertyCount'  => 1,
					'propertyLabels' => 'Bar',
					'propertyValues' => 'Foo Bar'
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
					'propertyCount'  => 2,
					'propertyLabels' => array( '状況', 'Bar' ),
					'propertyValues' => array( '超やばい', 'Http://www.semantic-mediawiki.org/w/index.php?title=Subobject' )
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
					'propertyCount'  => 2,
					'propertyLabels' => array( 'Bar', $diPropertyError->getLabel() ),
					'propertyValues' => array( 'Foo Bar', 'Modification date' )
				)
			),
		);
	}

	public function firstElementDataProvider() {
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
					'propertyCount'  => 2,
					'propertyLabels' => array( 'Bar', 'Foo bar foo' ),
					'propertyValues' => array( 'Foo Bar' ) // additional value is added during runtime
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
					'propertyCount'  => 1,
					'propertyLabels' => array( 'Bar' ),
					'propertyValues' => array( 'Foo Bar' )
				),
				array( 'msg' => 'Failed asserting the validity of the named identifier' )
			)
		);
	}

	public function sortKeyProvider() {

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
					new SMWDIProperty( 'Bar' ),
					new SMWDIProperty( '_SKEY' )
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
					new SMWDIProperty( 'Bar' )
				),
				'propertyValues' => array(
					'Foo Bar'
				)
			)
		);

		return $provider;
	}

}
