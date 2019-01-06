<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DeferredCallableUpdate;
use SMW\Localizer;
use SMW\Tests\Utils\Mock\ConfigurableStub;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @license GNU GPL v2+
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
		DeferredCallableUpdate::releasePendingUpdates();
		\DeferredUpdates::doUpdates();
	}

	/**
	 * @since 2.4
	 */
	public static function clearPendingDeferredUpdates() {
		DeferredCallableUpdate::releasePendingUpdates();
		\DeferredUpdates::clearPendingUpdates();
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

		// MW 1.27+ (yet 1.27.0.rc has no access to "resetServiceForTesting")
		if ( !class_exists( '\MediaWiki\MediaWikiServices' ) || !method_exists( \MediaWiki\MediaWikiServices::getInstance(), 'resetServiceForTesting' ) ) {
			return null;
		}

		try {
			\MediaWiki\MediaWikiServices::getInstance()->resetServiceForTesting( $name );
		} catch( \Exception $e ) {
			// Do nothing just avoid a
			// MediaWiki\Services\NoSuchServiceException: No such service ...
		}

		if ( $name === 'MainWANObjectCache' ) {
			\MediaWiki\MediaWikiServices::getInstance()->getMainWANObjectCache()->clearProcessCache();
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

		if ( !class_exists( '\MediaWiki\MediaWikiServices' ) ) {
			return null;
		}

		$this->resetMediaWikiService( $name );

		try {
			\MediaWiki\MediaWikiServices::getInstance()->redefineService( $name, $service );
		} catch( \Exception $e ) {
			// Do nothing just avoid a
			// MediaWiki\Services\NoSuchServiceException: No such service ...
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
	public function tearDown() {
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
	 * @param integer $index
	 * @param string $url
	 *
	 * @return string
	 */
	public function replaceNamespaceWithLocalizedText( $index, $text ) {

		$namespace = Localizer::getInstance()->getNamespaceTextById( $index );

		return str_replace(
			Localizer::getInstance()->getCanonicalNamespaceTextById( $index ) . ':',
			$namespace . ':',
			$text
		);
	}

}
