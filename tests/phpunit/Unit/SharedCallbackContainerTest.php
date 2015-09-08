<?php

namespace SMW\Tests;

use SMW\SharedCallbackContainer;

/**
 * @covers \SMW\SharedCallbackContainer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SharedCallbackContainerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SharedCallbackContainer',
			new SharedCallbackContainer()
		);

		$this->assertInstanceOf(
			'\Onoi\CallbackContainer\CallbackContainer',
			new SharedCallbackContainer()
		);
	}

	public function testRegister() {

		$callbackLoader = $this->getMockBuilder( '\Onoi\CallbackContainer\CallbackLoader' )
			->disableOriginalConstructor()
			->getMock();

		$callbackLoader->expects( $this->atLeastOnce() )
			->method( 'registerCallback' );

		$instance = new SharedCallbackContainer();
		$instance->register( $callbackLoader );
	}

}
