<?php

namespace SMW\Tests\Services;

use SMW\Services\SharedServicesContainer;

/**
 * @covers \SMW\Services\SharedServicesContainer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SharedServicesContainerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SharedServicesContainer::class,
			new SharedServicesContainer()
		);

		$this->assertInstanceOf(
			'\Onoi\CallbackContainer\CallbackContainer',
			new SharedServicesContainer()
		);
	}

	public function testRegister() {

		$containerBuilder = $this->getMockBuilder( '\Onoi\CallbackContainer\ContainerBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$containerBuilder->expects( $this->atLeastOnce() )
			->method( 'registerCallback' );

		$instance = new SharedServicesContainer();
		$instance->register( $containerBuilder );
	}

}
