<?php

namespace SMW\Tests;

use MediaWiki\MediaWikiServices;
use SMW\DataValueFactory;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class TestEnvironment {

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var TestConfig
	 */
	private $testConfig;

	/**
	 * @since 2.4
	 *
	 * @param array $configuration
	 */
	public function __construct( array $configuration = [] ) {
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->testConfig = new TestConfig();

		$this->withConfiguration( $configuration );
	}

	/**
	 * @since 2.4
	 */
	public static function executePendingDeferredUpdates() {
		CallableUpdate::releasePendingUpdates();
		\DeferredUpdates::doUpdates();
	}

	/**
	 * @since 2.4
	 */
	public static function clearPendingDeferredUpdates() {
		CallableUpdate::clearPendingUpdates();
		\DeferredUpdates::clearPendingUpdates();
	}

	/**
	 * @since 3.2
	 *
	 * @param array $defaultSettingKeys
	 */
	public static function loadDefaultSettings( array $defaultSettingKeys = [] ) {
		$settings = require $GLOBALS['smwgIP'] . '/includes/DefaultSettings.php';

		if ( $defaultSettingKeys !== [] ) {
			$copy = [];

			foreach ( $defaultSettingKeys as $key ) {
				$copy[$key] = $settings[$key];
			}

			$settings = $copy;
		}

		( new TestConfig() )->set( $settings );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return self
	 */
	public function addConfiguration( $key, $value ) {
		return $this->withConfiguration( [ $key => $value ] );
	}

	/**
	 * @since 2.4
	 *
	 * @param array $configuration
	 *
	 * @return self
	 */
	public function withConfiguration( array $configuration = [] ) {
		$this->testConfig->set( $configuration );
		return $this;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $name
	 */
	public function resetMediaWikiService( $name ) {
		try {
			MediaWikiServices::getInstance()->resetServiceForTesting( $name );
		} catch ( \Exception $e ) {
			// Do nothing just avoid a
			// MediaWiki\Services\NoSuchServiceException: No such service ...
		}

		return $this;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 * @param callable $service
	 */
	public function redefineMediaWikiService( $name, callable $service ) {
		$this->resetMediaWikiService( $name );

		try {
			MediaWikiServices::getInstance()->redefineService( $name, $service );
		} catch ( \Exception $e ) {
			// Do nothing just avoid a
			// MediaWiki\Services\NoSuchServiceException: No such service ...
		}
	}

	/**
	 * @since 3.1
	 */
	public static function changePrefix( $prefix ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new \RuntimeException( "Your are trying to change the `DomainPrefix` while not being in test!" );
		}

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$lbFactory->setLocalDomainPrefix( $prefix );

		$GLOBALS['wgDBprefix'] = $prefix;
	}

	/**
	 * @see https://github.com/wikimedia/mediawiki/commit/7b4eafda0d986180d20f37f2489b70e8eca00df4
	 * @since 3.2
	 */
	public static function overrideUserPermissions( $user, $permissions = [] ) {
		$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		$permissionManager->overrideUserRightsForTesting( $user, $permissions );
	}

	public function resetDBLoadBalancer() {
		try {
			// Get the MediaWiki service container
			$services = MediaWikiServices::getInstance();

			// Check if DBLoadBalancer is available
			if ( $services->has( 'DBLoadBalancer' ) ) {
				return;  // DBLoadBalancer is already initialized
			}

			// Reinitialize DBLoadBalancer if missing
			$services->set( 'DBLoadBalancer', new DBLoadBalancer() );
		} catch ( \Exception $e ) {
			// Handle exception or log
			error_log( 'Error resetting DBLoadBalancer: ' . $e->getMessage() );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param string|array $poolCache
	 *
	 * @return self
	 */
	public function resetPoolCacheById( $poolCache ) {
		if ( is_array( $poolCache ) ) {
			foreach ( $poolCache as $pc ) {
				$this->resetPoolCacheById( $pc );
			}
		}

		$this->applicationFactory->getInMemoryPoolCache()->resetPoolCacheById( $poolCache );

		return $this;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 * @param mixed $object
	 *
	 * @return self
	 */
	public function registerObject( $id, $object ) {
		$this->applicationFactory->registerObject( $id, $object );
		return $this;
	}

	/**
	 * @since 2.4
	 */
	public function tearDown(): void {
		$this->testConfig->reset();
		$this->applicationFactory->clear();
		$this->dataValueFactory->clear();
	}

	/**
	 * @since 2.5
	 *
	 * @param callable $callback
	 *
	 * @return string
	 */
	public function outputFromCallbackExec( callable $callback ) {
		ob_start();
		call_user_func( $callback );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $pages
	 */
	public function flushPages( $pages ) {
		self::getUtilityFactory()->newPageDeleter()->doDeletePoolOfPages( $pages );
	}

	/**
	 * @since 2.4
	 *
	 * @return UtilityFactory
	 */
	public static function getUtilityFactory() {
		return UtilityFactory::getInstance();
	}

	/**
	 * @since 3.0
	 *
	 * @return ValidatorFactory
	 */
	public static function newValidatorFactory() {
		return UtilityFactory::getInstance()->newValidatorFactory();
	}

	/**
	 * @since 3.0
	 *
	 * @return SpyLogger
	 */
	public static function newSpyLogger() {
		return self::getUtilityFactory()->newSpyLogger();
	}

	/**
	 * @since 2.5
	 *
	 * @param int $index
	 * @param string $text
	 *
	 * @return string
	 */
	public function replaceNamespaceWithLocalizedText( $index, $text ) {
		$namespace = Localizer::getInstance()->getNsText( $index );

		return str_replace(
			Localizer::getInstance()->getCanonicalNamespaceTextById( $index ) . ':',
			$namespace . ':',
			$text
		);
	}

}
