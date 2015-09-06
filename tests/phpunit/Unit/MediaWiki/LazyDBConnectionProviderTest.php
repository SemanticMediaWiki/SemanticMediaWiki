<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\LazyDBConnectionProvider;

use DatabaseBase;
use ReflectionClass;

/**
 * @covers \SMW\MediaWiki\LazyDBConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class LazyDBConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMW\MediaWiki\LazyDBConnectionProvider';
	}

	private function newInstance( $connectionId = DB_SLAVE, $groups = array(), $wiki = false ) {
		return new LazyDBConnectionProvider( $connectionId, $groups, $wiki );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	public function testGetAndReleaseConnection() {

		$instance   = $this->newInstance( DB_SLAVE );
		$connection = $instance->getConnection();

		$this->assertInstanceOf( 'DatabaseBase', $instance->getConnection() );

		$this->assertTrue(
			$instance->getConnection() === $connection,
			'Asserts that getConnection yields the same instance'
		);

		$instance->releaseConnection();

	}

	public function testGetConnectionThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$instance  = $this->newInstance();
		$reflector = new ReflectionClass( $this->getClass() );

		$connection = $reflector->getProperty( 'connection' );
		$connection->setAccessible( true );
		$connection->setValue( $instance, 'invalid' );

		$this->assertInstanceOf( 'DatabaseBase', $instance->getConnection() );

	}

}
