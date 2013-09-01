<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\ParserAfterTidy;

/**
 * Tests for the ParserAfterTidy class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ParserAfterTidy
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ParserAfterTidyTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParserAfterTidy';
	}

	/**
	 * Helper method that returns a ParserAfterTidy object
	 *
	 * @since 1.9
	 *
	 * @return ParserAfterTidy
	 */
	private function newInstance( &$parser = null, &$text = '' ) {

		if ( $parser === null ) {
			$parser = $this->newParser( $this->newTitle(), $this->getUser() );
		}

		$instance = new ParserAfterTidy( $parser, $text );
		$instance->setDependencyBuilder( $this->newDependencyBuilder( new SharedDependencyContainer() ) );

		return $instance;
	}

	/**
	 * @test ParserAfterTidy::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test ParserAfterTidy::process
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
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
		$cacheHandler   = $instance->getDependencyBuilder()->newObject( 'CacheHandler' );

		// Simulates a previous state change did cause a cache entry
		if ( $setup['cache'] ) {
			$cacheHandler->setKey(
				\SMW\ArticlePurge::newIdGenerator( $setup['title']->getArticleID() )
			)->set( __METHOD__ );
		}

		$container = $instance->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Settings', $settings );
		$container->registerObject( 'Store', $this->newMockObject()->getMockStore() );
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
	 * @test ParserAfterTidy::process
	 *
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
			'propertyCount' => 2,
			'propertyKey'   => array( '_INST', '_SKEY' ),
			'propertyValue' => array( 'Foo', 'Bar', $title->getText() ),
		);

		$instance = $this->newInstance( $parser, $text );

		$settings = $this->newSettings( array(
			'smwgCacheType'             => 'hash',
			'smwgEnableUpdateJobs'      => false,
			'smwgUseCategoryHierarchy'  => false,
			'smwgCategoriesAsInstances' => true
		) );

		$container = $instance->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Settings', $settings );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

		// Re-read data from the Parser
		$parserData = $this->getParserData( $title, $parser->getOutput() );
		$this->assertSemanticData(
			$parserData->getData(),
			$expected,
			'asserts whether the container contains expected triples'
		);

	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		// #0 Runs store update
		$title = $this->newMockObject( array(
			'inNamespace'     => false,
		) )->getMockTitle();

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
		$title = $this->newMockObject( array(
			'inNamespace'     => false,
		) )->getMockTitle();

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
		$title = $this->newMockObject( array(
			'isSpecialPage'   => true,
		) )->getMockTitle();

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
		$title = $this->newMockObject( array(
			'inNamespace'     => true,
			'getNamespace'    => NS_FILE
		) )->getMockTitle();

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
