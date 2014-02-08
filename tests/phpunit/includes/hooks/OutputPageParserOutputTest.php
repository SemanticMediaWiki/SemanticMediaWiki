<?php

namespace SMW\Test;

use SMW\OutputPageParserOutput;
use SMW\ExtensionContext;
use SMW\Settings;

use ParserOutput;

/**
 * @covers \SMW\OutputPageParserOutput
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutputTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\OutputPageParserOutput';
	}

	/**
	 * @since 1.9
	 *
	 * @return OutputPageParserOutput
	 */
	private function newInstance( &$outputPage = null , &$parserOutput = null ) {

		if ( $parserOutput === null ) {
			$parserOutput = $this->newMockBuilder()->newObject( 'ParserOutput' );
		}

		if ( $outputPage === null ) {
			$outputPage = $this->newMockBuilder()->newObject( 'OutputPage' );
		}

		$mockStore = $this->newMockBuilder()->newObject( 'Store' );

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Store', $mockStore );

		$instance = new OutputPageParserOutput( $outputPage, $parserOutput );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider outputDataProvider
	 *
	 * Verify that parseText works in isolation and independently from the
	 * class besides using the injected objects. The method is protected
	 * therefore ReflectionClass is used to gain access
	 *
	 * @since 1.9
	 */
	public function testProcess( $setup, $expected ) {

		$settings = Settings::newFromArray( array(
			'smwgNamespacesWithSemanticLinks' => $setup['smwgNamespacesWithSemanticLinks'],
			'smwgShowFactbox'                 => SMW_FACTBOX_NONEMPTY,
			'smwgFactboxUseCache'             => true,
			'smwgCacheType'                   => 'hash',
			'smwgLinksInValues'               => false,
			'smwgInlineErrors'                => true,
		) );

		$outputPage   = $setup['outputPage'];
		$parserOutput = $setup['parserOutput'];

		$instance = $this->newInstance( $outputPage, $parserOutput );
		$instance->withContext()->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		// Verify that for the invoked objects no previsous content is cached
		$factboxCache = $instance->withContext()->getDependencyBuilder()->newObject( 'FactboxCache', array(
			'OutputPage' => $outputPage
		) );

		$this->assertEmpty(
			$factboxCache->retrieveContent(),
			'Asserts that retrieveContent() returns an empty result before process()'
		);

		// Process
		$instance->process();

		// For expected content continue to verify that the outputPage was amended and
		// that the content is also available via the CacheStore
		if ( $expected['text'] !== '' ) {

			$text = $outputPage->mSMWFactboxText;

			$this->assertContains( $expected['text'], $text );
			$this->assertEquals(
				$text,
				$factboxCache->retrieveContent(),
				'Asserts that retrieveContent() returns an expected text'
			);

			// Deliberately clear the outputPage Property to retrieve
			// content from the CacheStore
			unset( $outputPage->mSMWFactboxText );
			$this->assertEquals(
				$text,
				$factboxCache->retrieveContent(),
				'Asserts that retrieveContent() is returning text from cache'
			);

		} else {

			$this->assertFalse(
				isset( $outputPage->mSMWFactboxText ),
				'Asserts that the property is not set'
			);

		}

	}

	/**
	 * @return array
	 */
	public function outputDataProvider() {

		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => __METHOD__ . 'mock-title',
			'getPageLanguage' => $this->getLanguage()
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $mockOutputPage->getTitle(),
			'getDBkey' => $mockOutputPage->getTitle()->getDBkey()
		) );

		$mockDIProperty = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => true,
			'isShown'       => true,
			'getLabel'      => __METHOD__ . 'property'
		) );

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'getSubject'           => $mockSubject,
			'hasVisibleProperties' => true,
			'getPropertyValues'    => array( $mockSubject ),
			'getProperties'        => array( $mockDIProperty )
		) );

		$provider = array();

		// #0 Simple factbox build, returning content
		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $mockOutputPage,
				'parserOutput' => $this->makeParserOutput( $mockSemanticData ),
			),
			array(
				'text'         => $mockTitle->getDBKey()
			)
		);

		// #1 Disabled namespace, no return value expected
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage()
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => false ),
				'outputPage'   => $mockOutputPage,
				'parserOutput' => $this->makeParserOutput( $mockSemanticData ),
			),
			array(
				'text'         => ''
			)
		);

		// #2 Specialpage, no return value expected
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
			'isSpecialPage'   => true
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $mockOutputPage,
				'parserOutput' => $this->makeParserOutput( $mockSemanticData ),
			),
			array(
				'text'         => ''
			)
		);

		// #3 Redirect, no return value expected
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
			'isRedirect'      => true
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $mockOutputPage,
				'parserOutput' => $this->makeParserOutput( $mockSemanticData ),
			),
			array(
				'text'         => ''
			)
		);

		// #4 Oldid
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext( array( 'oldid' => 9001 ) )
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'outputPage'   => $mockOutputPage,
				'parserOutput' => $this->makeParserOutput( $mockSemanticData ),
			),
			array(
				'text'         => $mockSubject->getDBKey()
			)
		);

		return $provider;
	}

	protected function makeParserOutput( $data ) {

		$parserOutput = new ParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $data );
		} else {
			$parserOutput->mSMWData = $data;
		}

		return $parserOutput;
	}


}
