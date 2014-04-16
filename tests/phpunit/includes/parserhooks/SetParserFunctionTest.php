<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\SetParserFunction;
use SMW\ParserData;
use SMW\ParserParameterFormatter;
use SMW\MessageFormatter;

use SMWDIWikiPage;
use SMWDataItem;
use Title;
use ParserOutput;

/**
 * @covers \SMW\SetParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */

class SetParserFunctionTest extends ParserTestCase {

	public function getClass() {
		return '\SMW\SetParserFunction';
	}

	/**
	 * @return  SetParserFunction
	 */
	private function newInstance( Title $title = null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		return new SetParserFunction(
			$this->newParserData( $title, $parserOutput ),
			new MessageFormatter( $title->getPageLanguage() )
		);
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testParse( array $params, array $expected ) {
		$instance = $this->newInstance( $this->newTitle(), $this->newParserOutput() );
		$result = $instance->parse( $this->getParserParameterFormatter( $params ) );

		$this->assertInternalType( 'string', $result );
	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testInstantiatedPropertyValues( array $params, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();

		// Initialize and parse
		$instance = $this->newInstance( $title, $parserOutput );
		$instance->parse( $this->getParserParameterFormatter( $params ) );

		// Re-read data from stored parserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( '\SMW\SemanticData', $parserData->getData() );

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $parserData->getSemanticData() );

	}

	/**
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->newParser( $this->newTitle(), $this->getUser() );
		$result = SetParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}

	/**
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// #0 Single data set
			// {{#set:
			// |Foo=bar
			// }}
			array(
				array( 'Foo=bar' ),
				array(
					'errors' => 0,
					'propertyCount'  => 1,
					'propertyLabels' => 'Foo',
					'propertyValues' => 'Bar'
				)
			),

			// #1 Empty data set
			// {{#set:
			// |Foo=
			// }}
			array(
				array( 'Foo=' ),
				array(
					'errors' => 0,
					'propertyCount'  => 0,
					'propertyLabels' => '',
					'propertyValues' => ''
				)
			),

			// #2 Multiple data set
			// {{#set:
			// |BarFoo=9001
			// |Foo=bar
			// }}
			array(
				array( 'Foo=bar', 'BarFoo=9001' ),
				array(
					'errors' => 0,
					'propertyCount'  => 2,
					'propertyLabels' => array( 'Foo', 'BarFoo' ),
					'propertyValues' => array( 'Bar', '9001' )
				)
			),

			// #3 Multiple data set with an error record
			// {{#set:
			// |_Foo=9001 --> will raise an error
			// |Foo=bar
			// }}
			array(
				array( 'Foo=bar', '_Foo=9001' ),
				array(
					'errors' => 1,
					'propertyCount'  => 1,
					'propertyLabels' => array( 'Foo' ),
					'propertyValues' => array( 'Bar' )
				)
			),

		);
	}

}
