<?php

namespace SMW\Test;

use SMW\AskParserFunction;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\QueryData;
use SMW\Settings;

use Title;
use ParserOutput;
use ReflectionClass;

/**
 * Tests for the AskParserFunction class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\AskParserFunction
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class AskParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\AskParserFunction';
	}

	/**
	 * Helper method that returns a AskParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return AskParserFunction
	 */
	private function getInstance( Title $title, ParserOutput $parserOutput = null, Settings $settings = null ) {

		if ( $settings === null ) {
			$settings = $this->newSettings();
		}

		return new AskParserFunction(
			$this->getParserData( $title, $parserOutput ),
			new QueryData( $title ),
			new MessageFormatter( $title->getPageLanguage() ),
			$settings
		);
	}

	/**
	 * @test AskParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->newTitle(), $this->newParserOutput() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test AskParserFunction::parse
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {

		$instance = $this->getInstance( $this->newTitle(), $this->newParserOutput() );
		$result  = $instance->parse( $params );
		$this->assertInternalType( 'string', $result );

	}

	/**
	 * @test AskParserFunction::parse (Test ($GLOBALS['smwgQEnabled'] = false))
	 *
	 * @since 1.9
	 */
	public function testParseDisabledsmwgQEnabled() {

		$title    = $this->newTitle();
		$message  = new MessageFormatter( $title->getPageLanguage() );
		$expected = $message->addFromKey( 'smw_iq_disabled' )->getHtml();

		$instance = $this->getInstance( $title , $this->newParserOutput() );

		// Make protected method accessible
		$reflection = new ReflectionClass( $this->getClass() );
		$method = $reflection->getMethod( 'disabled' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance );
		$this->assertEquals( $expected , $result );

	}

	/**
	 * @test AskParserFunction::parse
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedQueryData( array $params, array $expected, array $settings ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();

		// Initialize and parse
		$instance = $this->getInstance( $title, $parserOutput, $this->newSettings( $settings ) );
		$instance->parse( $params );

		// Get semantic data from the ParserOutput
		$parserData = $this->getParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $parserData->getData() );

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertSemanticData( $containerSemanticData, $expected );
		}

	}

	/**
	 * @test AskParserFunction::render
	 *
	 * @since 1.9
	 */
	public function testStaticRender() {
		$parser = $this->getParser( $this->newTitle(), $this->getUser() );
		$result = AskParserFunction::render( $parser );
		$this->assertInternalType( 'string', $result );
	}

	/**
	 * Provides sample data usually found in {{#ask}} queries
	 *
	 * @return array
	 */
	public function queryDataProvider() {

		$provider = array();

		// #0
		// {{#ask: [[Modification date::+]]
		// |?Modification date
		// |format=list
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]]',
				'?Modification date',
				'format=list'
			),
			array(
				'propertyCount' => 4,
				'propertyKey'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'list', 1, 1, '[[Modification date::+]]' )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #1 Query string with spaces
		// {{#ask: [[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]',
				'?Modification date',
				'?Has title',
				'format=list'
			),
			array(
				'propertyCount' => 4,
				'propertyKey'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'list', 4, 1, '[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]' )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #2
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=list'
			),
			array(
				'propertyCount' => 4,
				'propertyKey'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #3 Known format
		// {{#ask: [[File:Fooo]]
		// |?Modification date
		// |default=no results
		// |format=feed
		// }}
		$provider[] = array(
			array(
				'[[File:Fooo]]',
				'?Modification date',
				'default=no results',
				'format=feed'
			),
			array(
				'propertyCount' => 4,
				'propertyKey'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'feed', 1, 1, '[[:File:Fooo]]' )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #4 Unknown format, default table
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=bar
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=lula'
			),
			array(
				'propertyCount' => 4,
				'propertyKey'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValue' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		return $provider;
	}

}
