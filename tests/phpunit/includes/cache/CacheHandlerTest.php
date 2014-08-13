<?php

namespace SMW\Test;

use SMW\CacheIdGenerator;
use SMW\CacheHandler;

use HashBagOStuff;

/**
 * @covers \SMW\CacheHandler
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CacheHandlerTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\CacheHandler';
	}

	/**
	 * @note HashBagOStuff is used as test interface because it stores
	 * content in an associative array (which is not going to persist)
	 *
	 * @return CacheHandler
	 */
	private function newInstance() {
		return new CacheHandler( new HashBagOStuff );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
		$this->assertInstanceOf( 'BagOStuff', $this->newInstance()->getCache() );
	}

	/**
	 * @since 1.9
	 */
	public function testNewFromId() {

		CacheHandler::reset();

		$instance = CacheHandler::newFromId( 'hash' );

		$this->assertFalse(
			$instance->isEnabled(),
			'Asserts that with no key and valid cacheId, the cache is disabled'
		);

		$instance->setCacheEnabled( true )->setKey( new CacheIdGenerator( 'lila' ) );
		$this->assertTrue(
			$instance->isEnabled(),
			'Asserts that with an avilable key and valid cacheId, the cache is enabled'
		);

		// Static
		$this->assertTrue(
			$instance === CacheHandler::newFromId( 'hash' ),
			'Asserts a static instance'
		);

		$instance->reset();

		$this->assertTrue(
			$instance !== CacheHandler::newFromId( 'hash' ),
			'Asserts that the instance have been reset'
		);

		$instance = CacheHandler::newFromId( 'lula' );

		$this->assertFalse(
			$instance->isEnabled(),
			'Asserts that with no key and invalid cacheId, the cache is disabled'
		);

		$instance->setCacheEnabled( true )->setKey( new CacheIdGenerator( 'lila' ) );

		$this->assertFalse(
			$instance->isEnabled(),
			'Asserts that with an available key but invalid cacheId, the cache is disabled'
		);

		$this->assertTrue(
			$instance === CacheHandler::newFromId( 'lula' ),
			'Asserts a static instance'
		);

		$instance->reset();

		$this->assertTrue(
			$instance !== CacheHandler::newFromId( 'lula' ),
			'Asserts that the instance have been reset'
		);

	}

	/**
	 * @dataProvider keyItemDataProvider
	 *
	 * @since 1.9
	 */
	public function testEnabledCache( $key, $item ) {

		$instance = $this->newInstance();

		// Assert key handling
		$instance->setCacheEnabled( true )->setKey( new CacheIdGenerator( $key ) );
		$instanceKey = $instance->getKey();

		// Assert storage and retrieval
		$instance->set( $item );
		$this->assertEquals( $item, $instance->get() );

		// Assert deletion
		$instance->delete();

		$this->assertEmpty( $instance->get() );
		$this->assertEquals( $instanceKey, $instance->getKey() );

		// Set key
		$instance->setCacheEnabled( true )->setKey( new CacheIdGenerator( $key, 'test-prefix' ) );
		$this->assertContains( 'test-prefix' , $instance->getKey() );

	}

	/**
	 * @dataProvider keyItemDataProvider
	 *
	 * @since 1.9
	 */
	public function testDisabledCache( $key, $item ) {

		$instance = $this->newInstance();

		// Assert key handling
		$instance->setCacheEnabled( false )->setKey( new CacheIdGenerator( $key ) );
		$instanceKey = $instance->getKey();

		// Assert storage and retrieval
		$instance->set( $item );
		$this->assertEmpty( $instance->get() );

		// Assert deletion
		$instance->delete();

		$this->assertEmpty( $instance->get() );
		$this->assertEquals( $instanceKey, $instance->getKey() );
	}

	/**
	 * @return array
	 */
	public function keyItemDataProvider() {

		$key = $this->newRandomString( 10 );

		$item = array(
			$this->newRandomString( 10 ),
			$this->newRandomString( 20 )
		);

		return array( array( $key, $item ) );
	}
}
