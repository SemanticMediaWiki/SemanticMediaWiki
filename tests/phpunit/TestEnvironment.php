<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DeferredCallableUpdate;
use SMW\Store;
use SMW\Localizer;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\Utils\Mock\ConfigurableStub;
use RuntimeException;

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
	private $applicationFactory = null;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory = null;

	/**
	 * @var array
	 */
	private $configuration = array();

	/**
	 * @since 2.4
	 *
	 * @param array $configuration
	 */
	public function __construct( array $configuration = array() ) {
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();

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
		return $this->withConfiguration( array( $key => $value ) );
	}

	/**
	 * @since 2.4
	 *
	 * @param array $configuration
	 *
	 * @return self
	 */
	public function withConfiguration( array $configuration = array() ) {

		foreach ( $configuration as $key => $value ) {

			if ( isset( $GLOBALS[$key] ) || array_key_exists( $key, $GLOBALS ) ) {
				$this->configuration[$key] = $GLOBALS[$key];
				$GLOBALS[$key] = $value;
			}

			$this->applicationFactory->getSettings()->set( $key, $value );
		}

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

		foreach ( $this->configuration as $key => $value ) {
			$GLOBALS[$key] = $value;
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

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
	public function executeAndFetchOutputBufferContents( callable $callback ) {
		ob_start();
		call_user_func( $callback );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * @since 2.5
	 *
	 * @param $originalClassName
	 * @param array $configuration
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	public function createConfiguredStub( $originalClassName, array $configuration ) {
		$configurableStub = new ConfigurableStub();
		return $configurableStub->createConfiguredStub( $originalClassName, $configuration );
	}

	/**
	 * @since 2.5
	 *
	 * @param $originalClassName
	 * @param array $configuration
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	public function createConfiguredAbstractStub( $originalClassName, array $configuration ) {
		$configurableStub = new ConfigurableStub();
		return $configurableStub->createConfiguredAbstractStub( $originalClassName, $configuration );
	}

	/**
	 * @since 2.5
	 *
	 * @param array $pages
	 */
	public function flushPages( $pages ) {
		$this->getUtilityFactory()->newPageDeleter()->doDeletePoolOfPages( $pages );
	}

	/**
	 * @since 2.4
	 *
	 * @return UtilityFactory
	 */
	public function getUtilityFactory() {
		return UtilityFactory::getInstance();
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $index
	 * @param string $url
	 *
	 * @return string
	 */
	public function getLocalizedTextByNamespace( $index, $text ) {

		$namespace = Localizer::getInstance()->getNamespaceTextById( $index );

		return str_replace(
			Localizer::getInstance()->getCanonicalNamespaceTextById( $index ) . ':',
			$namespace . ':',
			$text
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $target
	 * @param string $file
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function getFixturesLocation( $target = '', $file = '' ) {

		$fixturesLocation = __DIR__ . '/Fixtures' . ( $target !== '' ? "/{$target}" :  '' ) . ( $file !== '' ? '/' . $file : '' );
		$fixturesLocation = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $fixturesLocation );

		if ( !file_exists( $fixturesLocation ) && !is_dir( $fixturesLocation ) ) {
			throw new RuntimeException( "{$fixturesLocation} does not exist." );
		}

		return $fixturesLocation;
	}

}
