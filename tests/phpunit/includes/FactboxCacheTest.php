<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\FactboxCache;

/**
 * @covers \SMW\FactboxCache
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
class FactboxCacheTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\FactboxCache';
	}

	/**
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
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testNewCacheId() {
		$this->assertInstanceOf( '\SMW\CacheIdGenerator', FactboxCache::newCacheId( 9001 ) );
	}

	/**
	 * @dataProvider outputDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcessAndRetrieveContentOnMock( $setup, $expected ) {

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => $setup['smwgNamespacesWithSemanticLinks'],
			'smwgShowFactbox'     => $setup['smwgShowFactbox'],
			'smwgFactboxUseCache' => true,
			'smwgCacheType'       => 'hash'
		);

		$outputPage = $setup['outputPage'];
		$instance   = $this->newInstance( $outputPage );

		$container = $instance->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Settings', $this->newSettings( $settings ) );
		$container->registerObject( 'Store', $setup['store'] );

		$this->assertEmpty(
			$instance->retrieveContent(),
			'Asserts that no previous content was cached'
		);

		$instance->process( $setup['parserOutput'] );
		$result = $outputPage->mSMWFactboxText;

		$this->assertPreProcess( $expected, $result, $outputPage, $instance );

		// Re-run on the same instance
		$instance->process( $setup['parserOutput'] );

		$this->assertPostProcess( $expected, $result, $outputPage, $instance );

	}

	/**
	 * @since 1.9
	 */
	public function assertPreProcess( $expected, $result, $outputPage, $instance ) {

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

	}

	/**
	 * @since 1.9
	 */
	public function assertPostProcess( $expected, $result, $outputPage, $instance ) {

		$this->assertEquals(
			$result,
			$instance->retrieveContent(),
			'Asserts that content is being fetched from cache'
		);

		$this->assertTrue(
			$result === $outputPage->mSMWFactboxText,
			'Asserts that content from the outputpage property and retrieveContent() is equal'
		);

		if ( $expected['text'] ) {

			$this->assertTrue(
				$instance->isCached(),
				'Asserts that isCached() returns true'
			);

		} else {

			$this->assertFalse(
				$instance->isCached(),
				'Asserts that isCached() returns false'
			);

		}

	}

	/**
	 * @return array
	 */
	public function outputDataProvider() {

		$provider = array();

		$mockStore = $this->newMockBuilder()->newObject( 'Store' );

		// #0 Factbox build, being visible
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $mockOutputPage,
				'store'           => $mockStore,
				'parserOutput'    => $this->makeParserOutput( $this->setupSematicData( $mockOutputPage, 'Queeey-0' ) )
			),
			array(
				'text'            => $mockTitle->getDBKey()
			)
		);

		// #1 Factbox build, being visible, using WebRequest oldid
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
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $mockOutputPage,
				'store'           => $mockStore,
				'parserOutput'    => $this->makeParserOutput( $this->setupSematicData( $mockOutputPage, 'Queeey-1' ) )
			),
			array(
				'text'            => $mockTitle->getDBKey()
			)
		);

		// #2 Factbox is expected not to be visible
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
				'store'           => $mockStore,
				'parserOutput'    => $this->makeParserOutput( $this->setupSematicData( $mockOutputPage, 'Queeey-2' ) )
			),
			array(
				'text'            => null
			)
		);

		// #3 No semantic data
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'isEmpty' => true
		) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getSemanticData' => $mockSemanticData
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $mockOutputPage,
				'store'           => $mockStore,
				'parserOutput'    => $this->makeParserOutput( null ),
			),
			array(
				'text'            => null
			)
		);

		// #4 SpecialPage
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true,
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $mockOutputPage,
				'store'           => $mockStore,
				'parserOutput'    => $this->makeParserOutput( null ),
			),
			array(
				'text'            => ''
			)
		);

		// #5 isDeleted
		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'isDeleted' => true,
		) );

		$mockOutputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle'   => $mockTitle,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $mockOutputPage,
				'store'           => $mockStore,
				'parserOutput'    => $this->makeParserOutput( null ),
			),
			array(
				'text'            => ''
			)
		);

		return $provider;
	}

	/**
	 * @return SemanticData
	 */
	protected function setupSematicData( $outputPage, $label ) {

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $outputPage->getTitle(),
			'getDBkey' => $outputPage->getTitle()->getDBkey()
		) );

		$mockDIProperty = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => true,
			'isShown'       => true,
			'getLabel'      => $label
		) );

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'hasVisibleProperties' => true,
			'isEmpty'              => false,
			'getSubject'           => $mockSubject,
			'getPropertyValues'    => array( $mockSubject ),
			'getProperties'        => array( $mockDIProperty )
		) );

		return $mockSemanticData;
	}

	/**
	 * @return ParserOutput
	 */
	protected function makeParserOutput( $semanticData ) {

		$parserOutput = $this->newParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $semanticData );
		} else {
			$parserOutput->mSMWData = $semanticData;
		}

		return $parserOutput;

	}

}
