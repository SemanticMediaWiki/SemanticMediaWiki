<?php

namespace SMW\Test;

use SMW\LazyDBConnectionProvider;

use DatabaseBase;

/**
 * @covers \SMW\LazyDBConnectionProvider
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class LazyDBConnectionProviderTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\LazyDBConnectionProvider';
	}

	/**
	 * @since 1.9
	 *
	 * @return LazyDBConnectionProvider
	 */
	private function newInstance( $connectionId = DB_SLAVE, $groups = array(), $wiki = false ) {
		return new LazyDBConnectionProvider( $connectionId, $groups, $wiki );
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
	public function testGetAndReleaseConnection() {

		$instance   = $this->newInstance( DB_SLAVE );
		$connection = $instance->getConnection();

		$this->assertInstanceOf( 'DatabaseBase', $instance->getConnection() );

		$this->assertTrue(
			$instance->getConnection() === $connection,
			'Asserts that getConnection() yields the same instance'
		);

		$instance->releaseConnection();

	}

	/**
	 * @since 1.9
	 */
	public function testGetConnectionOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance  = $this->newInstance();
		$reflector = $this->newReflector();

		$connection = $reflector->getProperty( 'connection' );
		$connection->setAccessible( true );
		$connection->setValue( $instance, 'invalid' );

		$this->assertInstanceOf( 'DatabaseBase', $instance->getConnection() );

	}

}
