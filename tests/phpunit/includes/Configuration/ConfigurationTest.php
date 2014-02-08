<?php

namespace SMW\Tests\Configuration;

use SMW\Configuration\Configuration;

use ReflectionClass;

/**
 * @covers \SMW\Configuration\Configuration
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMW\Configuration\Configuration';
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), new Configuration );
	}

	public function testGet() {

		$instance = new Configuration;

		$reflector = new ReflectionClass( $this->getClass() );
		$container = $reflector->getProperty( 'container' );
		$container->setAccessible( true );
		$container->setValue( $instance, array( 'Foo' => 'Fuyu' ) );

		$this->assertEquals( 'Fuyu', $instance->get( 'Foo' ) );

	}

	public function testGetThrowsException() {

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance = new Configuration;
		$this->assertEquals( 'Fuyu', $instance->get( 9001 ) );

	}

}
