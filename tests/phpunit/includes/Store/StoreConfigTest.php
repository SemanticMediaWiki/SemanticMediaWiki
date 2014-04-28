<?php

namespace SMW\Tests\Store;

use SMW\Store\StoreConfig;
use SMW\Settings;

/**
 * @uses \SMW\Store\StoreConfig
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class StoreConfigTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Store\StoreConfig',
			new StoreConfig
		);
	}

	public function testChangesOnStoreConfigDefaultInvocation() {

		$instance = new StoreConfig;

		$this->assertNotEmpty( $instance->get( 'smwgDefaultStore' ) );

		$this->assertInstanceOf(
			'\SMW\Store\StoreConfig',
			$instance->set( 'smwgDefaultStore', 'Foo' )
		);

		$this->assertEquals( 'Foo' , $instance->get( 'smwgDefaultStore' ) );
	}

	public function testGetOnUnsupportedKeyThrowsException() {

		$instance = new StoreConfig;

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

	public function testSetOnUnsupportedKeyThrowsException() {

		$instance = new StoreConfig;

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->set( 'Foo', 'Bar' );
	}

	public function testCreateInstanceOnEmptySettingsThrowsException() {

		$this->setExpectedException( 'SMW\InvalidSettingsArgumentException' );
		new StoreConfig( Settings::newFromArray( array() ) );
	}

}
