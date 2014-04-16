<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\ExtensionContext;
use SMW\AskParserFunction;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\QueryData;
use SMW\Settings;

use Title;
use ParserOutput;
use ReflectionClass;

/**
 * @covers \SMW\AskParserFunction
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
	 * @since 1.9
	 *
	 * @return AskParserFunction
	 */
	private function newInstance( Title $title = null, ParserOutput $parserOutput = null, Settings $settings = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		if ( $settings === null ) {
			$settings = $this->newSettings( array(
				'smwgQueryDurationEnabled' => false
			) );
		}

		$context = new ExtensionContext();
		$container = $context->getDependencyBuilder()->getContainer();

		$container->registerObject( 'MessageFormatter', new MessageFormatter( $title->getPageLanguage() ) );
		$container->registerObject( 'Settings', $settings );

		return new AskParserFunction(
			$this->newParserData( $title, $parserOutput ),
			$context
		);
	}

	/**
	 * @test AskParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
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

		$instance = $this->newInstance( $this->newTitle(), $this->newParserOutput() );
		$result  = $instance->parse( $params );
		$this->assertInternalType( 'string', $result );

	}

	/**
	 * @test AskParserFunction::parse (Test ($GLOBALS['smwgQEnabled'] = false))
	 *
	 * @since 1.9
	 */
	public function testIsQueryDisabled() {

		$title    = $this->newTitle();
		$message  = new MessageFormatter( $title->getPageLanguage() );

		$instance = $this->newInstance( $title , $this->newParserOutput() );

		$this->assertEquals(
			$message->addFromKey( 'smw_iq_disabled' )->getHtml(),
			$instance->isQueryDisabled(),
			'asserts a resutling disabled error message'
		);

	}

	/**
	 * @test AskParserFunction::setShowMode
	 *
	 * @since 1.9
	 */
	public function testSetShowMode() {

		$instance = $this->newInstance();

		$reflector = $this->newReflector();
		$showMode = $reflector->getProperty( 'showMode' );
		$showMode->setAccessible( true );

		$this->assertFalse(
			 $showMode->getValue( $instance ),
			'asserts that showMode is false by default'
		);

		$instance->setShowMode( true );

		$this->assertTrue(
			$showMode->getValue( $instance ),
			'asserts that showMode is true'
		);

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
		$instance = $this->newInstance( $title, $parserOutput, $this->newSettings( $settings ) );
		$instance->parse( $params );

		// Get semantic data from the ParserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( 'SMW\SemanticData', $parserData->getData() );
		$semanticDataValidator = new SemanticDataValidator;

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$semanticDataValidator->assertThatPropertiesAreSet( $expected, $containerSemanticData );
		}

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
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 1, 1, '[[Modification date::+]]' )
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
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 4, 1, '[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]' )
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
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
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
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'feed', 1, 1, '[[:File:Fooo]]' )
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
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #5 QueryTime enabled
		$provider[] = array(
			array(
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=lula'
			),
			array(
				'propertyCount'  => 5,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO', '_ASKDU' ),
			),
			array(
				'smwgQueryDurationEnabled' => true
			)
		);

		return $provider;
	}

}
