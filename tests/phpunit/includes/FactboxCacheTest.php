<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\FactboxCache;

/**
 * Tests for the FactboxCache class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\FactboxCache
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class FactboxCacheTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FactboxCache';
	}

	/**
	 * Helper method that returns a FactboxCache object
	 *
	 * @since 1.9
	 *
	 * @return FactboxCache
	 */
	private function newInstance( &$outputPage = null ) {

		if ( $outputPage === null ) {
			$outputPage = $this->newMockBuilder()->newObject( 'OutputPage' );
		}

		$container = new SharedDependencyContainer();
		$container->registerObject( 'Settings', $this->newSettings() );
		$container->registerObject( 'Store', $this->newMockBuilder()->newObject( 'Store' ) );

		$instance = new FactboxCache( $outputPage );
		$instance->setDependencyBuilder( $this->newDependencyBuilder( $container ) );

		return $instance;
	}

	/**
	 * @test FactboxCache::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test FactboxCache::process
	 * @test FactboxCache::retrieveContent
	 * @dataProvider outputDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testProcess( $setup, $expected ) {

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => $setup['smwgNamespacesWithSemanticLinks'],
			'smwgShowFactbox'                 => $setup['smwgShowFactbox'],
			'smwgFactboxUseCache'             => true,
			'smwgCacheType'                   => 'hash'
		);

		$outputPage = $setup['outputPage'];
		$instance   = $this->newInstance( $outputPage );

		$container = $instance->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Settings', $this->newSettings( $settings ) );

		// Verifies that no previous content is cached
		$this->assertEmpty( $instance->retrieveContent() );

		$instance->process( $setup['parserOutput'] );
		$result = $outputPage->mSMWFactboxText;

		if ( $expected['text'] ) {

			$this->assertContains(
				$expected['text'],
				$result,
				'Asserts that content was altered as expected'
			);

			// Deliberately clear the outputPage property to force
			// content to be retrieved from the cache
			unset( $outputPage->mSMWFactboxText );
			$this->assertTrue(
				$result === $instance->retrieveContent(),
				'Asserts that cached content was retrievable'
			);

		} else {
			$this->assertNull(
				$result,
				'Asserts that the result is null'
			);
		}

		// Re-run on the same instance
		$instance->process( $setup['parserOutput'] );

		$this->assertEquals(
			$result,
			$instance->retrieveContent(),
			'Asserts that content is being fetched from cache'
		);

		$this->assertTrue(
			$result === $outputPage->mSMWFactboxText,
			'Asserts that content from the outputpage property and retrieveContent() is equal'
		);

		$this->assertTrue(
			$instance->isCached(),
			'Asserts that isCached() returns true'
		);

	}

	/**
	 * @return array
	 */
	public function outputDataProvider() {

		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
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
			'getLabel'      => 'Queeey'
		) );

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'hasVisibleProperties' => true,
			'getSubject'           => $mockSubject,
			'getPropertyValues'    => array( $mockSubject ),
			'getProperties'        => array( $mockDIProperty )
		) );

		$parserOutput = $this->newParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $mockSemanticData );
		} else {
			$parserOutput->mSMWData = $mockSemanticData;
		}

		$provider = array();

		// #0 Factbox build, being visible
		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $mockOutputPage,
				'parserOutput'    => $parserOutput,
			),
			array(
				'text'            => $mockTitle->getDBKey()
			)
		);

		// #1 Factbox is expected not to be visible
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
				'smwgShowFactbox' => SMW_FACTBOX_HIDDEN,
				'outputPage'      => $mockOutputPage,
				'parserOutput'    => $parserOutput,
			),
			array(
				'text'            => null
			)
		);

		// #2 No semantic data
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$parserOutput = $this->newParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', null );
		} else {
			$parserOutput->mSMWData = null;
		}

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $mockOutputPage,
				'parserOutput'    => $parserOutput,
			),
			array(
				'text'            => null
			)
		);

		return $provider;
	}

}
