<?php

namespace SMW\Tests;

use SMW\SetupFile;
use SMW\Utils\File;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SetupFile
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SetupFileTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testIsGoodSchema() {
		$this->assertIsBool(

			SetupFile::isGoodSchema()
		);
	}

	public function testMakeUpgradeKey() {
		$var1 = [
			'smwgUpgradeKey' => '',
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Foo', 'Bar' ],
			'smwgPageSpecialProperties' => [ 'Foo', 'Bar' ],
			'smwgFieldTypeFeatures' => false,
			'smwgEntityCollation' => ''
		];

		$var2 = [
			'smwgUpgradeKey' => '',
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Bar', 'Foo' ],
			'smwgPageSpecialProperties' => [ 'Bar', 'Foo' ],
			'smwgFieldTypeFeatures' => false,
			'smwgEntityCollation' => ''
		];

		$this->assertEquals(
			SetupFile::makeUpgradeKey( $var1 ),
			SetupFile::makeUpgradeKey( $var2 )
		);
	}

	public function testMakeUpgradeKey_SpecialFixedProperties() {
		$var1 = [
			'smwgUpgradeKey' => '',
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Foo', 'Bar' ],
			'smwgPageSpecialProperties' => [ 'Foo', 'Bar' ],
			'smwgFieldTypeFeatures' => false,
			'smwgEntityCollation' => ''
		];

		$var2 = [
			'smwgUpgradeKey' => '',
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [ 'Bar', 'Foo' ],
			'smwgPageSpecialProperties' => [ 'Bar', '_MDAT' ],
			'smwgFieldTypeFeatures' => false,
			'smwgEntityCollation' => ''
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
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [],
			'smwgFieldTypeFeatures' => false,
			'smwgEntityCollation' => ''
		];

		$instance->finalize( $vars );
	}

	public function testSetMaintenanceMode() {
		$fields = [
			'upgrade_key' => '2fefe0755c8b2d1b13b22a0a0c0677a24982ad3e',
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
				$expected );

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smwgConfigFileDir' => 'Foo/',
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [],
			'smwgFieldTypeFeatures' => false,
			'smwgEntityCollation' => 'identity'
		];

		$instance->setMaintenanceMode( true, $vars );
	}

	public function testSetUpgradeFile() {
		$configFile = File::dir( 'Foo_dir/.smw.json' );

		$fields = [
			'Foo' => 42,
			// "upgrade_key_base" => '["",[],"",[]]'
		];

		$expected = json_encode( [ \SMW\Site::id() => $fields ], JSON_PRETTY_PRINT );

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$file->expects( $this->once() )
			->method( 'write' )
			->with(
				$configFile,
				$expected );

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smwgConfigFileDir' => 'Foo_dir',
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [],
			'smwgFieldTypeFeatures' => false
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
				$configFile,
				$expected );

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
				$configFile,
				$expected );

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

	public function testGet() {
		$id = \SMW\Site::id();

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smw.json' => [ $id => [ 'Foo' => 42 ] ]
		];

		$this->assertEquals(
			42,
			$instance->get( 'Foo', $vars )
		);
	}

	public function testAddRemoveIncompleteTask() {
		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetupFile(
			$file
		);

		$instance->addIncompleteTask( 'foo-incomplete' );

		$this->assertArrayHasKey(
			'foo-incomplete',
			$instance->get( 'incomplete_tasks' )
		);

		$instance->removeIncompleteTask( 'foo-incomplete' );

		$this->assertArrayNotHasKey(
			'foo-incomplete',
			$instance->get( 'incomplete_tasks' )
		);
	}

	public function testIncompleteTasks() {
		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smw.json' => [ \SMW\Site::id() => [ \SMW\SQLStore\Installer::POPULATE_HASH_FIELD_COMPLETE => false ] ]
		];

		$this->assertEquals(
			[ 'smw-install-incomplete-populate-hash-field' ],
			$instance->findIncompleteTasks( $vars )
		);

		$this->assertEquals(
			[],
			$instance->findIncompleteTasks( [ 'foo' ] )
		);
	}

	public function testSetLatestVersion() {
		$id = \SMW\Site::id();

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetupFile(
			$file
		);

		// No previous version is known
		$instance->setLatestVersion( 123 );

		$this->assertEquals(
			123,
			$instance->get( SetupFile::LATEST_VERSION )
		);

		$this->assertNull(
						$instance->get( SetupFile::PREVIOUS_VERSION, [ 'smw.json' => [] ] )
		);

		// Previous version is known
		$instance->setLatestVersion( 456 );

		$this->assertEquals(
			456,
			$instance->get( SetupFile::LATEST_VERSION )
		);

		$this->assertEquals(
			123,
			$instance->get( SetupFile::PREVIOUS_VERSION )
		);
	}

	public function testHasDatabaseMinRequirement() {
		$id = \SMW\Site::id();

		$file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetupFile(
			$file
		);

		$vars = [
			'smw.json' => [ $id => [ 'Foo' => 42 ] ]
		];

		// No requirement entry
		$this->assertTrue(
			$instance->hasDatabaseMinRequirement( $vars )
		);

		// Doesn't match the the `minimum_version`
		$vars = [
			'smw.json' => [ $id => [ SetupFile::DB_REQUIREMENTS => [ 'latest_version' => '2', 'minimum_version' => '3' ] ] ]
		];

		$this->assertFalse(
			$instance->hasDatabaseMinRequirement( $vars )
		);

		$vars = [
			'smw.json' => [ $id => [ SetupFile::DB_REQUIREMENTS => [ 'latest_version' => '3.1', 'minimum_version' => '3' ] ] ]
		];

		// Does match the the `minimum_version`
		$this->assertTrue(
			$instance->hasDatabaseMinRequirement( $vars )
		);
	}

}
