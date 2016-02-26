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

}
