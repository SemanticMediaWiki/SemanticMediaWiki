<?php

namespace SMW\Tests\Services;

use Onoi\CallbackContainer\CallbackContainer;
use Onoi\CallbackContainer\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use SMW\Services\SharedServicesContainer;

/**
 * @covers \SMW\Services\SharedServicesContainer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class SharedServicesContainerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SharedServicesContainer::class,
			new SharedServicesContainer()
		);

		$this->assertInstanceOf(
			CallbackContainer::class,
			new SharedServicesContainer()
		);
	}

	public function testRegister() {
		$containerBuilder = $this->getMockBuilder( ContainerBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$containerBuilder->expects( $this->atLeastOnce() )
			->method( 'registerCallback' );

		$instance = new SharedServicesContainer();
		$instance->register( $containerBuilder );
	}

}
