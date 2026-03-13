<?php

namespace SMW\Tests;

use Exception;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\MediaWikiServices;
use SMW\DataValueFactory;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\Utils\SpyLogger;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\Utils\Validators\ValidatorFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class TestEnvironment {

	private ApplicationFactory $applicationFactory;

	private DataValueFactory $dataValueFactory;

	private TestConfig $testConfig;

	/**
	 * @since 2.4
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
	public static function executePendingDeferredUpdates(): void {
		CallableUpdate::releasePendingUpdates();
		DeferredUpdates::doUpdates();
	}

	/**
	 * @since 2.4
	 */
	public static function clearPendingDeferredUpdates(): void {
		CallableUpdate::clearPendingUpdates();
		DeferredUpdates::clearPendingUpdates();
	}

	/**
	 * Disable MediaWiki software change tags (mw-new-redirect, mw-blank,
	 * etc.) to avoid MariaDB 11.8+ error 1020 ("Record has changed since
	 * last read") caused by ChangeTagsStore::updateTags reading and then
	 * updating change_tag_def within the same transaction.
	 *
	 * @since 6.1.0
	 */
	public function disableSoftwareChangeTags(): void {
		$GLOBALS['wgSoftwareTags'] = [
			'mw-contentmodelchange' => false,
			'mw-new-redirect' => false,
			'mw-removed-redirect' => false,
			'mw-changed-redirect-target' => false,
			'mw-blank' => false,
			'mw-replace' => false,
			'mw-rollback' => false,
			'mw-undo' => false,
			'mw-manual-revert' => false,
			'mw-reverted' => false,
			'mw-server-side-upload' => false,
		];
	}

	/**
	 * @since 3.2
	 */
	public static function loadDefaultSettings( array $defaultSettingKeys = [] ): void {
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
	 */
	public function addConfiguration( string $key, $value ): self {
		return $this->withConfiguration( [ $key => $value ] );
	}

	/**
	 * @since 2.4
	 */
	public function withConfiguration( array $configuration = [] ): self {
		$this->testConfig->set( $configuration );
		return $this;
	}

	/**
	 * @since 2.4
	 */
	public function resetMediaWikiService( string $name ): self {
		try {
			MediaWikiServices::getInstance()->resetServiceForTesting( $name );
		} catch ( Exception $e ) {
			// Do nothing just avoid a
			// MediaWiki\Services\NoSuchServiceException: No such service ...
		}

		return $this;
	}

	/**
	 * @since 3.0
	 */
	public function redefineMediaWikiService( string $name, callable $service ): void {
		$this->resetMediaWikiService( $name );

		try {
			MediaWikiServices::getInstance()->redefineService( $name, $service );
		} catch ( Exception $e ) {
			// Do nothing just avoid a
			// MediaWiki\Services\NoSuchServiceException: No such service ...
		}
	}

	/**
	 * @see https://github.com/wikimedia/mediawiki/commit/7b4eafda0d986180d20f37f2489b70e8eca00df4
	 * @since 3.2
	 */
	public static function overrideUserPermissions( $user, array $permissions = [] ): void {
		$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		$permissionManager->overrideUserRightsForTesting( $user, $permissions );
	}

	/**
	 * @since 2.4
	 */
	public function resetPoolCacheById( string|array $poolCache ): self {
		if ( is_array( $poolCache ) ) {
			foreach ( $poolCache as $pc ) {
				$this->resetPoolCacheById( $pc );
			}

			return $this;
		}

		$this->applicationFactory->getInMemoryPoolCache()->resetPoolCacheById( $poolCache );

		return $this;
	}

	/**
	 * @since 2.4
	 */
	public function registerObject( string $id, $object ): self {
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
	 */
	public function outputFromCallbackExec( callable $callback ): string {
		ob_start();
		call_user_func( $callback );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * @since 2.5
	 */
	public function flushPages( array $pages ): void {
		self::getUtilityFactory()->newPageDeleter()->doDeletePoolOfPages( $pages );
	}

	/**
	 * @since 2.4
	 */
	public static function getUtilityFactory(): UtilityFactory {
		return UtilityFactory::getInstance();
	}

	/**
	 * @since 3.0
	 */
	public static function newValidatorFactory(): ValidatorFactory {
		return UtilityFactory::getInstance()->newValidatorFactory();
	}

	/**
	 * @since 3.0
	 */
	public static function newSpyLogger(): SpyLogger {
		return self::getUtilityFactory()->newSpyLogger();
	}

	/**
	 * @since 2.5
	 */
	public function replaceNamespaceWithLocalizedText( int $index, string $text ): string {
		$namespace = Localizer::getInstance()->getNsText( $index );

		return str_replace(
			Localizer::getInstance()->getCanonicalNamespaceTextById( $index ) . ':',
			$namespace . ':',
			$text
		);
	}

}
