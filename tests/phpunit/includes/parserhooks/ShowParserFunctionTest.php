<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\ExtensionContext;
use SMW\ShowParserFunction;
use SMW\MessageFormatter;
use SMW\QueryData;

use Title;
use ParserOutput;

/**
 * @covers \SMW\ShowParserFunction
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
class ShowParserFunctionTest extends ParserTestCase {

	public function getClass() {
		return '\SMW\ShowParserFunction';
	}

	/**
	 * @return ShowParserFunction
	 */
	private function newInstance( Title $title = null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		$settings = $this->newSettings( array(
			'smwgQueryDurationEnabled' => false
		) );

		$context = new ExtensionContext();
		$container = $context->getDependencyBuilder()->getContainer();

		$container->registerObject( 'MessageFormatter', new MessageFormatter( $title->getPageLanguage() ) );
		$container->registerObject( 'Settings', $settings );

		return new ShowParserFunction(
			$this->newParserData( $title, $parserOutput ),
			$context
		 );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testParse( array $params, array $expected ) {

		$instance = $this->newInstance( $this->newTitle(), $this->newParserOutput() );
		$result   = $instance->parse( $params, true );

		if (  $expected['output'] === '' ) {
			$this->assertEmpty( $result );
		} else {
			$this->assertContains( $expected['output'], $result );
		}

	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testIsQueryDisabled() {

		$title    = $this->newTitle();
		$message  = new MessageFormatter( $title->getPageLanguage() );
		$expected = $message->addFromKey( 'smw_iq_disabled' )->getHtml();

		$instance = $this->newInstance( $title, $this->getParserOutput() );

		$this->assertEquals( $expected , $instance->isQueryDisabled() );
	}

	/**
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testInstantiatedQueryData( array $params, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();

		// Initialize and parse
		$instance = $this->newInstance( $title, $parserOutput );
		$instance->parse( $params );

		// Get semantic data from the ParserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( '\SMW\SemanticData', $parserData->getData() );
		$semanticDataValidator = new SemanticDataValidator;

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$semanticDataValidator->assertThatPropertiesAreSet( $expected, $containerSemanticData );
		}

	}

	/**
	 * @return array
	 */
	public function queryDataProvider() {

		$provider = array();

		// #0
		// {{#show: Foo
		// |?Modification date
		// }}
		$provider[] = array(
			array(
				'Foo',
				'?Modification date',
			),
			array(
				'output' => '',
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
				'propertyValues' => array( 'list', 0, 1, '[[:Foo]]' )
			)
		);

		// #1
		// {{#show: Help:Bar
		// |?Modification date
		// |default=no results
		// }}
		$provider[] = array(
			array(
				'Help:Bar',
				'?Modification date',
				'default=no results'
			),
			array(
				'output' => 'no results',
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
				'propertyValues' => array( 'list', 0, 1, '[[:Help:Bar]]' )
			)
		);

		// #2 [[..]] is not acknowledged therefore displays an error message
		// {{#show: [[File:Fooo]]
		// |?Modification date
		// |default=no results
		// |format=table
		// }}
		$provider[] = array(
			array(
				'[[File:Fooo]]',
				'?Modification date',
				'default=no results',
				'format=table'
			),
			array(
				'output' => 'class="smwtticon warning"', // lazy content check for the error
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
				'propertyValues' => array( 'table', 0, 1, '[[:]]' )
			)
		);

		return $provider;
	}
}
