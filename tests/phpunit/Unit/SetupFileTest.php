<?php

namespace SMW\Tests;

use SMW\SetupFile;
use SMW\Utils\File;

/**
 * @covers \SMW\SetupFile
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SetupFileTest extends \PHPUnit_Framework_TestCase {

	public function testIsGoodSchema() {

		$this->assertInternalType(
			'boolean',
			SetupFile::isGoodSchema()
		);
	}

	public function testMakeUpgradeKey() {

		$var1 = [
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Foo', 'Bar' ],
			'smwgPageSpecialProperties' => [ 'Foo', 'Bar' ]
		];

		$var2 = [
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Bar', 'Foo' ],
			'smwgPageSpecialProperties' => [ 'Bar', 'Foo' ]
		];

		$this->assertEquals(
			SetupFile::makeUpgradeKey( $var1 ),
			SetupFile::makeUpgradeKey( $var2 )
		);
	}

	public function testMakeUpgradeKey_SpecialFixedProperties() {

		$var1 = [
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Foo', 'Bar' ],
			'smwgPageSpecialProperties' => [ 'Foo', 'Bar' ]
		];

		$var2 = [
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Bar', 'Foo' ],
			'smwgPageSpecialProperties' => [ 'Bar', '_MDAT' ]
		];

		$this->assertNotEquals(
			SetupFile::makeUpgradeKey( $var1 ),
			SetupFile::makeUpgradeKey( $var2 )
		);
	}

	public function testFinalize() {

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'write' );

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smwgConfigFileDir' => 'Foo/',
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => []
		];

		$instance->finalize( $vars );
	}

	public function testSetMaintenanceMode() {

		$fields = [
			'upgrade_key' => 'abede9f6b2c43db901f6255e26b8d951f84f5d7c',
			SetupFile::MAINTENANCE_MODE => true,
			// "upgrade_key_base" => '["",[],"",[]]'
		];

		$expected = json_encode( [ \SMW\Site::id() => $fields ], JSON_PRETTY_PRINT );

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->anything(),
				$this->equalTo( $expected ) );

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smwgConfigFileDir' => 'Foo/',
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => []
		];

		$instance->setMaintenanceMode( true, $vars );
	}

	public function testSetUpgradeFile() {

		$configFile = File::dir( 'Foo_dir/.smw.json' );

		$fields = [
			'Foo' => 42,
			//"upgrade_key_base" => '["",[],"",[]]'
		];

		$expected = json_encode( [ \SMW\Site::id() => $fields ], JSON_PRETTY_PRINT );

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->equalTo( $configFile ),
				$this->equalTo( $expected ) );

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smwgConfigFileDir' => 'Foo_dir',
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => []
		];

		$instance->write( [ 'Foo' => 42 ], $vars );
	}

	public function testReset() {

		$configFile = File::dir( 'Foo_dir/.smw.json' );
		$id = \SMW\Site::id();

		$fields = [
			'Foo' => 42
		];

		$expected = json_encode( [ $id => [] ], JSON_PRETTY_PRINT );

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->equalTo( $configFile ),
				$this->equalTo( $expected ) );

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smwgConfigFileDir' => 'Foo_dir',
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [],
			'smw.json' => [ $id => $fields ]
		];

		$instance->reset( $vars );
	}

	public function testRemove() {

		$configFile = File::dir( 'Foo_dir/.smw.json' );
		$expected = '[]';

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->equalTo( $configFile ),
				$this->equalTo( $expected ) );

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smwgConfigFileDir' => 'Foo_dir',
			'smwgIP' => '',
			'smwgUpgradeKey' => 'bar',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => []
		];

		$instance->remove( 'Foo', $vars );
	}

	public function testIncompleteTasks() {

		$vars = [
			'smw.json' => [ \SMW\Site::id() => [ \SMW\SQLStore\Installer::POPULATE_HASH_FIELD_COMPLETE => false ] ]
		];

		$this->assertEquals(
			[ 'smw-install-incomplete-populate-hash-field' ],
			SetupFile::findIncompleteTasks( $vars )
		);

		$this->assertEquals(
			[],
			SetupFile::findIncompleteTasks( [] )
		);
	}

}
