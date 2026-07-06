<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SMW\SetupFile;
use SMW\Site;
use SMW\SmwJsonRepo;
use SMW\SQLStore\Installer;

/**
 * @covers \SMW\SetupFile
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SetupFileTest extends TestCase {

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

	public function testMakeUpgradeKey_FieldTypeFeaturesFormEquivalence() {
		// Legacy SMW_FIELDT_* bitmask form and the new array-of-strings form
		// describe the same configuration; admins switching between them must
		// not trigger a forced maintenance-mode upgrade (#6586).
		$common = [
			'smwgUpgradeKey' => '',
			'smwgDefaultStore' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [],
			'smwgEntityCollation' => '',
		];

		$legacy = $common + [
			'smwgFieldTypeFeatures' => SMW_FIELDT_CHAR_NOCASE | SMW_FIELDT_CHAR_LONG,
		];

		$newForm = $common + [
			'smwgFieldTypeFeatures' => [ 'char-nocase', 'char-long' ],
		];

		$this->assertEquals(
			SetupFile::makeUpgradeKey( $legacy ),
			SetupFile::makeUpgradeKey( $newForm )
		);

		// And the false sentinel still produces a different hash than the
		// flags-set form (component skipped vs. component registered with flags).
		$disabled = $common + [
			'smwgFieldTypeFeatures' => false,
		];

		$this->assertNotEquals(
			SetupFile::makeUpgradeKey( $legacy ),
			SetupFile::makeUpgradeKey( $disabled )
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
		$repo = $this->makeInMemoryRepo();

		$instance = new SetupFile();
		$this->withRepo( $instance, $repo );

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

		$id = Site::id();
		$this->assertSame(
			SetupFile::makeUpgradeKey( $vars ),
			$repo->data[$id][SetupFile::UPGRADE_KEY]
		);
		$this->assertFalse( $repo->data[$id][SetupFile::MAINTENANCE_MODE] );
	}

	public function testSetMaintenanceMode() {
		$repo = $this->makeInMemoryRepo();

		$instance = new SetupFile();
		$this->withRepo( $instance, $repo );

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

		$id = Site::id();
		$this->assertSame(
			[
				'upgrade_key' => '2fefe0755c8b2d1b13b22a0a0c0677a24982ad3e',
				SetupFile::MAINTENANCE_MODE => true,
			],
			$repo->data[$id]
		);
	}

	public function testSetUpgradeFile() {
		$repo = $this->makeInMemoryRepo();

		$instance = new SetupFile();
		$this->withRepo( $instance, $repo );

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

		$this->assertSame(
			[ Site::id() => [ 'Foo' => 42 ] ],
			$repo->data
		);
	}

	public function testReset() {
		$repo = $this->makeInMemoryRepo();
		$id = Site::id();

		$instance = new SetupFile();
		$this->withRepo( $instance, $repo );

		$vars = [
			'smwgConfigFileDir' => 'Foo_dir',
			'smwgIP' => '',
			'smwgUpgradeKey' => '',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => [],
			'smw.json' => [ $id => [ 'Foo' => 42 ] ]
		];

		$instance->reset( $vars );

		$this->assertSame( [ $id => [] ], $repo->data );
	}

	public function testRemove() {
		$repo = $this->makeInMemoryRepo();

		$instance = new SetupFile();
		$this->withRepo( $instance, $repo );

		$vars = [
			'smwgConfigFileDir' => 'Foo_dir',
			'smwgIP' => '',
			'smwgUpgradeKey' => 'bar',
			'smwgEnabledFulltextSearch' => '',
			'smwgFixedProperties' => [],
			'smwgPageSpecialProperties' => []
		];

		$instance->remove( 'Foo', $vars );

		// `remove` should not create an entry for the key being removed.
		$this->assertSame( [], $repo->data );
	}

	public function testGet() {
		$id = Site::id();

		$instance = new SetupFile();
		$this->withRepo( $instance, $this->makeInMemoryRepo() );

		$vars = [
			'smw.json' => [ $id => [ 'Foo' => 42 ] ]
		];

		$this->assertEquals(
			42,
			$instance->get( 'Foo', $vars )
		);
	}

	public function testAddRemoveIncompleteTask() {
		$instance = new SetupFile();
		$this->withRepo( $instance, $this->makeInMemoryRepo() );

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
		$instance = new SetupFile();
		$this->withRepo( $instance, $this->makeInMemoryRepo() );

		$vars = [
			'smw.json' => [ Site::id() => [ Installer::POPULATE_HASH_FIELD_COMPLETE => false ] ]
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
		$instance = new SetupFile();
		$this->withRepo( $instance, $this->makeInMemoryRepo() );

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
		$id = Site::id();

		$instance = new SetupFile();
		$this->withRepo( $instance, $this->makeInMemoryRepo() );

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

	public function testIncompleteTaskWithArgsRoundTrip() {
		$repo = $this->makeInMemoryRepo();
		$vars = $this->makeVars();

		$writer = new SetupFile();
		$this->withRepo( $writer, $repo );
		$writer->loadSchema( $vars );

		// Cover both the boolean form (no args) and the array form
		// (with args) of an incomplete-task entry.
		$writer->set(
			[
				SetupFile::INCOMPLETE_TASKS => [
					'smw-task-flag' => true,
					'smw-task-x' => [ 'count' => 42 ],
				],
			],
			$vars
		);

		// Round-trip via a fresh instance against the same repo.
		$readerVars = $this->makeVars();
		$reader = new SetupFile();
		$this->withRepo( $reader, $repo );
		$reader->loadSchema( $readerVars );

		$this->assertSame(
			[
				'smw-task-flag',
				[ 'smw-task-x', [ 'count' => 42 ] ],
			],
			$reader->findIncompleteTasks( $readerVars )
		);
	}

	public function testEntityCollationRoundTrip() {
		$repo = $this->makeInMemoryRepo();
		$vars = $this->makeVars();

		$writer = new SetupFile();
		$this->withRepo( $writer, $repo );
		$writer->loadSchema( $vars );

		$writer->set( [ SetupFile::ENTITY_COLLATION => 'identity' ], $vars );

		$readerVars = $this->makeVars();
		$reader = new SetupFile();
		$this->withRepo( $reader, $repo );
		$reader->loadSchema( $readerVars );

		$this->assertSame(
			'identity',
			$reader->get( SetupFile::ENTITY_COLLATION, $readerVars )
		);
	}

	public function testLastOptimizationRunRoundTrip() {
		$repo = $this->makeInMemoryRepo();
		$vars = $this->makeVars();

		$writer = new SetupFile();
		$this->withRepo( $writer, $repo );
		$writer->loadSchema( $vars );

		$writer->set( [ SetupFile::LAST_OPTIMIZATION_RUN => '2026-05-27 03:06' ], $vars );

		$readerVars = $this->makeVars();
		$reader = new SetupFile();
		$this->withRepo( $reader, $repo );
		$reader->loadSchema( $readerVars );

		$this->assertSame(
			'2026-05-27 03:06',
			$reader->get( SetupFile::LAST_OPTIMIZATION_RUN, $readerVars )
		);
	}

	public function testHasDatabaseMinRequirementPasses() {
		$vars = [
			'smw.json' => [
				Site::id() => [
					SetupFile::DB_REQUIREMENTS => [
						'latest_version' => '5.7',
						'minimum_version' => '5.5',
					],
				],
			],
		];

		$file = new SetupFile();

		$this->assertTrue( $file->hasDatabaseMinRequirement( $vars ) );
	}

	public function testHasDatabaseMinRequirementFails() {
		$vars = [
			'smw.json' => [
				Site::id() => [
					SetupFile::DB_REQUIREMENTS => [
						'latest_version' => '5.5',
						'minimum_version' => '5.7',
					],
				],
			],
		];

		$file = new SetupFile();

		$this->assertFalse( $file->hasDatabaseMinRequirement( $vars ) );
	}

	private function makeVars(): array {
		return [
			'smwgConfigFileDir' => sys_get_temp_dir(),
		];
	}

	private function makeInMemoryRepo(): SmwJsonRepo {
		return new class implements SmwJsonRepo {
			public array $data = [];

			public function loadSmwJson( string $configDirectory ): ?array {
				return $this->data;
			}

			public function saveSmwJson( string $configDirectory, array $smwJson ): void {
				$this->data = $smwJson;
			}
		};
	}

	private function withRepo( SetupFile $file, SmwJsonRepo $repo ): void {
		$ref = new ReflectionProperty( $file, 'repo' );
		$ref->setValue( $file, $repo );
	}

}
