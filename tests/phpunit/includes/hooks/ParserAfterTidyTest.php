<?php

namespace SMW\Test;

use SMW\ParserAfterTidy;
use SMW\ExtensionContext;

/**
 * @covers \SMW\ParserAfterTidy
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
class ParserAfterTidyTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParserAfterTidy';
	}

	/**
	 * @since 1.9
	 *
	 * @return CacheHandler
	 */
	private function newMockCacheHandler( $id, $status ) {

		$cacheHandler = $this->getMockBuilder( 'SMW\CacheHandler' )
			->disableOriginalConstructor()
			->getMock();

		$cacheHandler->expects( $this->any() )
			->method( 'setKey' )
			->with( $this->equalTo( \SMW\ArticlePurge::newCacheId( $id ) ) );

		$cacheHandler->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( $status ) );

		return $cacheHandler;

	}

	/**
	 * @since 1.9
	 *
	 * @return ParserAfterTidy
	 */
	private function newInstance( &$parser = null, &$text = '' ) {

		if ( $parser === null ) {
			$parser = $this->newParser( $this->newTitle(), $this->getUser() );
		}

		$instance = new ParserAfterTidy( $parser, $text );
		$instance->invokeContext( new ExtensionContext() );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcess( $setup, $expected ) {

		$parser = $this->newParser( $setup['title'], $this->getUser() );
		$text   = '';

		$instance = $this->newInstance( $parser, $text );
		$settings = $this->newSettings( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$updateObserver = new MockUpdateObserver();
		$mockStore = $this->newMockBuilder()->newObject( 'Store' );

		$cacheHandler = $this->newMockCacheHandler( $setup['title']->getArticleID(), $setup['cache']  );

		$container = $instance->withContext()->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Settings', $settings );
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'UpdateObserver', $updateObserver );
		$container->registerObject( 'CacheHandler', $cacheHandler );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

		$this->assertEquals(
			$expected['observer'],
			$updateObserver->getNotifier(),
			'asserts that that the invoked observer was notified'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testSemanticDataParserOuputUpdateIntegration() {

		$text   = '';
		$title  = $this->newTitle( NS_MAIN, __METHOD__ );

		// Set-up categories
		$parser = $this->newParser( $title, $this->getUser() );
		$parser->getOutput()->addCategory( 'Foo', 'Foo' );
		$parser->getOutput()->addCategory( 'Bar', 'Bar' );

		// Expected semantic data
		$expected = array(
			'propertyCount'  => 2,
			'propertyKeys'   => array( '_INST', '_SKEY' ),
			'propertyValues' => array( 'Foo', 'Bar', $title->getText() ),
		);

		$instance = $this->newInstance( $parser, $text );

		$settings = $this->newSettings( array(
			'smwgCacheType'             => 'hash',
			'smwgEnableUpdateJobs'      => false,
			'smwgUseCategoryHierarchy'  => false,
			'smwgCategoriesAsInstances' => true,
			'smwgShowHiddenCategories'  => true
		) );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Settings', $settings );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

		// Re-read data from the Parser
		$parserData = $this->newParserData( $title, $parser->getOutput() );

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $parserData->getSemanticData() );

	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		// #0 Runs store update
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'inNamespace'     => false,
			'getArticleID'    => 9001
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'cache'    => true
			),
			array(
				'observer' => 'runStoreUpdater'
			)
		);

		// #1 No cache entry, no store update
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'inNamespace'     => false,
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'cache'    => false
			),
			array(
				'observer' => null
			)
		);

		// #2 SpecialPage, no store update
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage'   => true,
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'cache'    => false
			),
			array(
				'observer' => null
			)
		);

		// #3 NS_FILE, no store update
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'inNamespace'     => true,
			'getNamespace'    => NS_FILE
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'cache'    => true
			),
			array(
				'observer' => null
			)
		);

		return $provider;
	}

}
