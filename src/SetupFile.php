<?php

namespace SMW;

use FileFetcher\SimpleFileFetcher;
use MediaWiki\MediaWikiServices;
use RuntimeException;
use SMW\Elastic\ElasticStore;
use SMW\Setup\LegacyConstantNormalizer;
use SMW\SQLStore\Installer;
use SMW\Utils\File;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SetupFile {

	/**
	 * Describes the maintenance mode
	 */
	const MAINTENANCE_MODE = 'maintenance_mode';

	/**
	 * Describes the upgrade key
	 */
	const UPGRADE_KEY = 'upgrade_key';

	/**
	 * Describes the database requirements
	 */
	const DB_REQUIREMENTS = 'db_requirements';

	/**
	 * Describes the entity collection setting
	 */
	const ENTITY_COLLATION = 'entity_collation';

	/**
	 * Key that describes the date of the last table optimization run.
	 */
	const LAST_OPTIMIZATION_RUN = 'last_optimization_run';

	/**
	 * Describes the file name
	 */
	public const FILE_NAME = '.smw.json';

	/**
	 * Describes incomplete tasks
	 */
	const INCOMPLETE_TASKS = 'incomplete_tasks';

	/**
	 * Versions
	 */
	const LATEST_VERSION = 'latest_version';
	const PREVIOUS_VERSION = 'previous_version';

	private const SMW_JSON = 'smw.json';

	private /* SmwJsonRepo */ $repo;

	public function __construct() {
		$this->repo = $GLOBALS['smwgSmwJsonRepo'] ??
			new DatabaseMetaRepo(
				MediaWikiServices::getInstance()->getDBLoadBalancer()
			);
	}

	public function loadSchema( array &$vars = [] ): array {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		if ( isset( $vars[self::SMW_JSON] ) ) {
			return $vars;
		}

		$smwJson = $this->repo->loadSmwJson( $vars['smwgConfigFileDir'] );

		// Pre-7.0 legacy-file fallback. When the primary repo is empty
		// (typically `smw_meta` on a wiki that hasn't been migrated yet),
		// hydrate `$vars` from `.smw.json` if it is still present. The
		// install/upgrade run then preserves the user's keys via
		// `SetupFile::write`'s merge-then-save semantics, and
		// {@see \SMW\Setup\MigrateSmwJsonToDb} consumes the current wiki's
		// slice from the file at the end of the install. Once the slice
		// is consumed (or the file was never present), this branch never
		// fires again.
		if ( $smwJson === null ) {
			$legacyPath = rtrim( $vars['smwgConfigFileDir'], '/' ) . '/' . self::FILE_NAME;
			if ( is_file( $legacyPath ) ) {
				$legacy = ( new FileSystemSmwJsonRepo( new SimpleFileFetcher(), new File() ) )
					->loadSmwJson( $vars['smwgConfigFileDir'] );
				if ( $legacy === null ) {
					// The file exists but `FileSystemSmwJsonRepo` could not
					// decode it. Aborting here is the only way to surface
					// the failure before the installer writes fresh rows to
					// `smw_meta` and permanently blocks the retry path: on
					// the next run `smw_meta` would be non-empty, this
					// fallback would not fire, and the legacy file (even
					// after the admin repairs it) would be silently ignored.
					throw new RuntimeException(
						"SMW: legacy {$legacyPath} exists but cannot be decoded as JSON."
						. " Fix or remove the file before retrying setup."
					);
				}
				$smwJson = $legacy;
			}
		}

		if ( $smwJson !== null ) {
			$vars[self::SMW_JSON] = $smwJson;
		}

		return $vars;
	}

	public static function isGoodSchema( bool $isCli = false ): bool {
		if ( $isCli && defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		if ( !$isCli && ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) ) {
			return true;
		}

		// #3563, Use the specific wiki-id as identifier for the instance in use
		$id = Site::id();

		if ( !isset( $GLOBALS[self::SMW_JSON][$id]['upgrade_key'] ) ) {
			return false;
		}

		if (
			isset( $GLOBALS[self::SMW_JSON][$id][self::MAINTENANCE_MODE] ) &&
			$GLOBALS[self::SMW_JSON][$id][self::MAINTENANCE_MODE] !== false ) {
			return false;
		}

		return self::makeUpgradeKey( $GLOBALS ) === $GLOBALS[self::SMW_JSON][$id]['upgrade_key'];
	}

	public static function makeUpgradeKey( array $vars ): string {
		return sha1( self::makeKey( $vars ) );
	}

	public function inMaintenanceMode( array $vars = [] ): bool {
		if ( !defined( 'MW_PHPUNIT_TEST' ) && ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) ) {
			return false;
		}

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		if ( !isset( $vars[self::SMW_JSON][$id][self::MAINTENANCE_MODE] ) ) {
			return false;
		}

		return $vars[self::SMW_JSON][$id][self::MAINTENANCE_MODE] !== false;
	}

	public function getMaintenanceMode( array $vars = [] ) {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		if ( !isset( $vars[self::SMW_JSON][$id][self::MAINTENANCE_MODE] ) ) {
			return [];
		}

		return $vars[self::SMW_JSON][$id][self::MAINTENANCE_MODE];
	}

	/**
	 * Tracking the latest and previous version, which allows us to decide whether
	 * current activities relate to an install (new) or upgrade.
	 */
	public function setLatestVersion( $version ): void {
		$latest = $this->get( self::LATEST_VERSION );
		$previous = $this->get( self::PREVIOUS_VERSION );

		if ( $latest === null && $previous === null ) {
			$this->set(
				[
					self::LATEST_VERSION => $version
				]
			);
		} elseif ( $latest !== $version ) {
			$this->set(
				[
					self::LATEST_VERSION => $version,
					self::PREVIOUS_VERSION => $latest
				]
			);
		}
	}

	public function addIncompleteTask( string $key, array $args = [] ): void {
		$incomplete_tasks = $this->get( self::INCOMPLETE_TASKS );

		if ( $incomplete_tasks === null ) {
			$incomplete_tasks = [];
		}

		$incomplete_tasks[$key] = $args === [] ? true : $args;

		$this->set( [ self::INCOMPLETE_TASKS => $incomplete_tasks ] );
	}

	public function removeIncompleteTask( string $key ): void {
		$incomplete_tasks = $this->get( self::INCOMPLETE_TASKS );

		if ( $incomplete_tasks === null ) {
			$incomplete_tasks = [];
		}

		unset( $incomplete_tasks[$key] );

		$this->set( [ self::INCOMPLETE_TASKS => $incomplete_tasks ] );
	}

	public function hasDatabaseMinRequirement( array $vars = [] ): bool {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		// No record means, no issues!
		if ( !isset( $vars[self::SMW_JSON][$id][self::DB_REQUIREMENTS] ) ) {
			return true;
		}

		$requirements = $vars[self::SMW_JSON][$id][self::DB_REQUIREMENTS];

		return version_compare( $requirements['latest_version'], $requirements['minimum_version'], 'ge' );
	}

	public function findIncompleteTasks( array $vars = [] ): array {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();
		$tasks = [];

		// Key field => [ value that constitutes the `INCOMPLETE` state, error msg ]
		$checks = [
			Installer::POPULATE_HASH_FIELD_COMPLETE => [ false, 'smw-install-incomplete-populate-hash-field' ],
			ElasticStore::REBUILD_INDEX_RUN_COMPLETE => [ false, 'smw-install-incomplete-elasticstore-indexrebuild' ]
		];

		foreach ( $checks as $key => $value ) {

			if ( !isset( $vars[self::SMW_JSON][$id][$key] ) ) {
				continue;
			}

			if ( $vars[self::SMW_JSON][$id][$key] === $value[0] ) {
				$tasks[] = $value[1];
			}
		}

		if ( isset( $vars[self::SMW_JSON][$id][self::INCOMPLETE_TASKS] ) ) {
			foreach ( $vars[self::SMW_JSON][$id][self::INCOMPLETE_TASKS] as $key => $args ) {
				if ( $args === true ) {
					$tasks[] = $key;
				} else {
					$tasks[] = [ $key, $args ];
				}
			}
		}

		return $tasks;
	}

	/**
	 * FIXME: a bunch of callers are calling with a single array argument. These are likely broken.
	 */
	public function setMaintenanceMode( $maintenanceMode, array $vars = [] ): void {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$this->write(
			[
				self::UPGRADE_KEY => self::makeUpgradeKey( $vars ),
				self::MAINTENANCE_MODE => $maintenanceMode
			],
			$vars
		);
	}

	public function finalize( array $vars = [] ): void {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		// #3563, Use the specific wiki-id as identifier for the instance in use
		$key = self::makeUpgradeKey( $vars );
		$id = Site::id();

		if (
			isset( $vars[self::SMW_JSON][$id][self::UPGRADE_KEY] ) &&
			$key === $vars[self::SMW_JSON][$id][self::UPGRADE_KEY] &&
			$vars[self::SMW_JSON][$id][self::MAINTENANCE_MODE] === false ) {
			return;
		}

		$this->write(
			[
				self::UPGRADE_KEY => $key,
				self::MAINTENANCE_MODE => false
			],
			$vars
		);
	}

	public function reset( array $vars = [] ): void {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();
		$args = [];

		if ( !isset( $vars[self::SMW_JSON][$id] ) ) {
			return;
		}

		$vars[self::SMW_JSON][$id] = [];

		$this->write( [], $vars );
	}

	public function set( array $args, $vars = [] ): void {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$this->write( $args, $vars );
	}

	public function get( string $key, array $vars = [] ) {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		if ( isset( $vars[self::SMW_JSON][$id][$key] ) ) {
			return $vars[self::SMW_JSON][$id][$key];
		}

		return null;
	}

	public function remove( string $key, $vars = [] ): void {
		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$this->write( [ $key => null ], $vars );
	}

	public function write( array $args, array $vars ): void {
		$id = Site::id();

		if ( !isset( $vars[self::SMW_JSON] ) ) {
			$vars[self::SMW_JSON] = [];
		}

		foreach ( $args as $key => $value ) {
			// NULL means that the key key is removed
			if ( $value === null ) {
				unset( $vars[self::SMW_JSON][$id][$key] );
				unset( $GLOBALS[self::SMW_JSON][$id][$key] );
			} else {
				$vars[self::SMW_JSON][$id][$key] = $value;
				$GLOBALS[self::SMW_JSON][$id][$key] = $value;
			}
		}

		// Log the base elements used for computing the key
		// $vars['smw.json'][$id]['upgrade_key_base'] = self::makeKey(
		//	$vars
		// );

		// Remove legacy
		if ( isset( $vars[self::SMW_JSON]['upgradeKey'] ) ) {
			unset( $vars[self::SMW_JSON]['upgradeKey'] );
			unset( $GLOBALS[self::SMW_JSON]['upgradeKey'] );
		}
		if ( isset( $vars[self::SMW_JSON][$id]['in.maintenance_mode'] ) ) {
			unset( $vars[self::SMW_JSON][$id]['in.maintenance_mode'] );
			unset( $GLOBALS[self::SMW_JSON][$id]['in.maintenance_mode'] );
		}

		try {
			$this->repo->saveSmwJson( $vars['smwgConfigFileDir'], $vars[self::SMW_JSON] );
		} catch ( RuntimeException $e ) {
			// Users may not have `wgShowExceptionDetails` enabled and would
			// therefore not see the exception error message hence we fail hard
			// and die
			die(
				"\n\nERROR: " . $e->getMessage() . "\n" .
				"\n       The \"smwgConfigFileDir\" setting should point to a" .
				"\n       directory that is persistent and writable!\n"
			);
		}
	}

	/**
	 * Listed keys will have a "global" impact of how data are stored, formatted,
	 * or represented in Semantic MediaWiki. In most cases it will require an action
	 * from an administrator when one of those keys are altered.
	 */
	private static function makeKey( array $vars ): string {
		// Only recognize those properties that require a fixed table
		$pageSpecialProperties = array_intersect(
			// Special properties enabled?
			$vars['smwgPageSpecialProperties'],

			// Any custom fixed properties require their own table?
			TypesRegistry::getFixedProperties( 'custom_fixed' )
		);

		$pageSpecialProperties = array_unique( $pageSpecialProperties );

		// Sort to ensure the key contains the same order
		sort( $vars['smwgFixedProperties'] );
		sort( $pageSpecialProperties );

		// The following settings influence the "shape" of the tables required
		// therefore use the content to compute a key that reflects any
		// changes to them
		$components = [
			$vars['smwgUpgradeKey'],
			$vars['smwgDefaultStore'],
			$vars['smwgFixedProperties'],
			$vars['smwgEnabledFulltextSearch'],
			$pageSpecialProperties
		];

		// Only add the key when it is different from the default setting
		if ( $vars['smwgEntityCollation'] !== 'identity' ) {
			$components += [ 'smwgEntityCollation' => $vars['smwgEntityCollation'] ];
		}

		// Normalize before hashing so the upgrade key is stable across the
		// PR-C migration: an admin switching from the legacy SMW_FIELDT_*
		// bitmask form to the new ["char-nocase", "char-long"] array form
		// must not trigger a forced maintenance-mode upgrade for what is
		// semantically the same configuration (#6586).
		$fieldTypeFeatures = LegacyConstantNormalizer::normalize(
			'smwgFieldTypeFeatures',
			$vars['smwgFieldTypeFeatures']
		);
		if ( $fieldTypeFeatures !== false ) {
			$components += [ 'smwgFieldTypeFeatures' => $fieldTypeFeatures ];
		}

		// Recognize when the version requirements change and force
		// an update to be able to check the requirements
		$components += Setup::MINIMUM_DB_VERSION;

		return json_encode( $components );
	}

}
