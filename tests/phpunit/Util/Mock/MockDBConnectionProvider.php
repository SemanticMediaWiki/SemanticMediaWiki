<?php

namespace SMW\Tests\Util\Mock;

use SMW\DBConnectionProvider;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class MockDBConnectionProvider extends \PHPUnit_Framework_TestCase implements DBConnectionProvider {

	public function getConnection() {
		return $this->createMockDBConnectionProvider()->getConnection();
	}

	public function releaseConnection() {}

	public function getMockDatabase() {

		if ( !isset( $this->database ) ) {
			$this->database = $this->getMockBuilder( 'DatabaseMysql' )
				->disableOriginalConstructor()
				->getMock();
		}

		return $this->database;
	}

	protected function createMockDBConnectionProvider() {

		$provider = $this->getMockForAbstractClass( '\SMW\DBConnectionProvider' );

		$provider->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->getMockDatabase() ) );

		return $provider;
	}

}
