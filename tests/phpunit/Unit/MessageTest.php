<?php

namespace SMW\Tests;

use SMW\Message;

/**
 * @covers \SMW\Message
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.4
 *
 * @author mwjames
 */
class MessageTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;

	public function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->resetPoolCacheFor( Message::POOLCACHE_ID );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Message',
			new Message()
		);
	}

	public function testEmptyStringOnUnregisteredHandler() {

		$instance = new Message();

		$this->assertEmpty(
			$instance->get( 'Foo', 'Foo' )
		);
	}

	public function testRegisteredHandler() {

		$instance = new Message();

		$instance->registerCallbackHandler( 'Foo', function( $parameters, $language ) {

			if ( $parameters[0] === 'Foo' && $language === Message::CONTENT_LANGUAGE ) {
				return 'Foobar';
			}

			if ( $parameters[0] === 'Foo' && is_string( $language ) ) {
				return $language;
			}

			return 'UNKNOWN';
		} );

		$this->assertEquals(
			'Foobar',
			$instance->get( 'Foo', 'Foo' )
		);

		$this->assertEquals(
			'en',
			$instance->get( 'Foo', 'Foo', 'en' )
		);

		$instance->deregisterHandlerFor( 'Foo' );
	}

	public function testRegisteredHandlerWithLanguage() {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'getCode' )
			->will( $this->returnValue( 'en' ) );

		$instanceSpy = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'hasLanguage' ) )
			->getMock();

		$instanceSpy->expects( $this->once() )
			->method( 'hasLanguage' )
			->with( $this->identicalTo( $language ) );

		$instance = new Message();
		$instance->clear();

		$instance->registerCallbackHandler( 'Foo', function( $parameters, $language ) use ( $instanceSpy ){
			$instanceSpy->hasLanguage( $language );
			return 'UNKNOWN';
		} );

		$instance->get( 'Bar', 'Foo', $language );
		$instance->deregisterHandlerFor( 'Foo' );
	}

	public function testFromCache() {

		$instance = new Message();
		$instance->clear();

		$instance->registerCallbackHandler( 'SimpleText', function( $parameters, $language ) {
			return 'Foo';
		} );

		$instance->get( 'Foo', 'SimpleText' );

		$this->assertEquals(
			array(
				'inserts' => 1,
				'deletes' => 0,
				'max'     => 1000,
				'count'   => 1,
				'hits'    => 0,
				'misses'  => 1
			),
			$instance->getCache()->getStats()
		);

		$instance->get( 'Foo', 'SimpleText', 'ooo' );

		$this->assertEquals(
			array(
				'inserts' => 2,
				'deletes' => 0,
				'max'     => 1000,
				'count'   => 2,
				'hits'    => 0,
				'misses'  => 2
			),
			$instance->getCache()->getStats()
		);

		// Repeated request
		$instance->get( 'Foo', 'SimpleText' );

		$this->assertEquals(
			array(
				'inserts' => 2,
				'deletes' => 0,
				'max'     => 1000,
				'count'   => 2,
				'hits'    => 1,
				'misses'  => 2
			),
			$instance->getCache()->getStats()
		);

		$instance->deregisterHandlerFor( 'SimpleText' );
	}

}
