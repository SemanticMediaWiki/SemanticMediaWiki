<?php

namespace SMW;

use SMW\Connection\ConnectionManager;
use SMW\MediaWiki\Hooks;
use SMW\Utils\Logo;
use SMW\GroupPermissions;
use SMW\MediaWiki\HookDispatcherAwareTrait;

/**
 * Extension setup and registration
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
final class Setup {

	use HookDispatcherAwareTrait;

	/**
	 * Describes the minimum requirements for the database version that Semantic
	 * MediaWiki expects and may differ from what is defined in:
	 *
	 * - `MysqlInstaller`
	 * - `PostgresInstaller`
	 * - `SqliteInstaller`
	 *
	 * Any change to a version will modify the key computed by `SetupFile::makeKey`.
	 */
	const MINIMUM_DB_VERSION = [
		'postgres' => '9.5',
		'sqlite' => '3.3.7',
		'mysql' => '5.5.8'
	];

	/**
	 * Registers a hook even before the "early" registration to allow checking
	 * whether the extension is loaded and enabled.
	 *
	 * @since 3.1
	 *
	 * @param array $vars
	 */
	public static function registerExtensionCheck( &$vars ) {
		$uncaughtExceptionHandler = new UncaughtExceptionHandler(
			SetupCheck::newFromDefaults()
		);

		// Register an exception handler to fetch the "Uncaught Exception" which
		// is can be thrown by the `ExtensionRegistry` in case `enableSemantics`
		// and `wfLoadExtension( 'SemanticMediaWiki' )` were used simultaneously
		// by emitting something like:
		//
		// "... It was attempted to load SemanticMediaWiki twice ..."
		set_exception_handler( [ $uncaughtExceptionHandler, 'registerHandler' ] );

		if ( $vars['smwgIgnoreExtensionRegistrationCheck'] ) {
			return;
		}

		Hooks::registerExtensionCheck( $vars );
	}

	/**
	 * @since 3.2
	 *
	 * @param array $vars
	 */
	public static function releaseExtensionCheck( &$vars ) {
		// Restore the exception handler from before Setup::registerExtensionCheck
		// and before MediaWiki setup has added its own in `Setup.php` after
		// declaring `MW_SERVICE_BOOTSTRAP_COMPLETE` using
		// `MWExceptionHandler::installHandler`.
		if ( !defined( 'MW_SERVICE_BOOTSTRAP_COMPLETE' ) ) {
			restore_exception_handler();
		}
	}

	/**
	 * Runs at the earliest possible event to initialize functions or hooks that
	 * are otherwise too late for the hook system to be recognized.
	 *
	 * @since 3.0
	 */
	public static function initExtension( array $vars ): array {
		Hooks::registerEarly( $vars );

		return $vars;
	}

	/**
	 * @since 3.0
	 */
	public static function isEnabled() {
		return defined( 'SMW_VERSION' ) && defined( 'SMW_EXTENSION_LOADED' );
	}

	/**
	 * @since 3.0
	 */
	public static function isValid( $isCli = false ) {
		return SetupFile::isGoodSchema( $isCli );
	}

	/**
	 * @since 1.9
	 */
	public function init( array $vars, string $rootDir ): array {
		$setupFile = new SetupFile();
		$vars = $setupFile->loadSchema( $vars );
		Globals::replace( $vars );

		if ( !$vars['smwgIgnoreUpgradeKeyCheck'] ) {
			$this->runUpgradeKeyCheck( $setupFile, $vars );
		}

		$this->initConnectionProviders();
		$this->initMessageCallbackHandler();
		$this->addDefaultConfigurations( $vars, $rootDir );

		$this->registerJobClasses( $vars );
		$this->registerPermissions( $vars );

		$this->registerParamDefinitions( $vars );
		$this->registerFooterIcon( $vars, $rootDir );
		$this->registerHooks( $vars );

		$this->hookDispatcher->onSetupAfterInitializationComplete( $vars );

		return $vars;
	}

	private function runUpgradeKeyCheck( SetupFile $setupFile, array $vars ): void {
		$setupCheck = new SetupCheck(
			[
				'SMW_VERSION' => SMW_VERSION,
				'MW_VERSION'  => MW_VERSION,
				'wgLanguageCode' => $vars['wgLanguageCode'],
				'smwgUpgradeKey' => $vars['smwgUpgradeKey']
			],
			$setupFile
		);

		if ( $setupCheck->hasError() ) {

			// If classified as `ERROR_EXTENSION_LOAD` then it means `extension.json`
			// was invoked by `wfLoadExtension( 'SemanticMediaWiki' )` at this
			// point which we don't allow as it conflicts with the setup of
			// namespaces and other settings hence we reclassify the error as an
			// invalid access.
			if ( $setupCheck->isError( SetupCheck::ERROR_EXTENSION_LOAD ) ) {
				$setupCheck->setErrorType( SetupCheck::ERROR_EXTENSION_INVALID_ACCESS );
			}

			$setupCheck->showErrorAndAbort( $setupCheck->isCli() );
		}
	}

	private function addDefaultConfigurations( &$vars, $rootDir ) {
		// Convenience function for extensions depending on a SMW specific
		// test infrastructure
		if ( !defined( 'SMW_PHPUNIT_AUTOLOADER_FILE' ) ) {
			$smwDir = dirname( $rootDir );
			define( 'SMW_PHPUNIT_AUTOLOADER_FILE', "$smwDir/tests/autoloader.php" );
		}

		$vars['wgLogTypes'][] = 'smw';
		$vars['wgFilterLogTypes']['smw'] = true;

		$vars['smwgMasterStore'] = null;
		$vars['smwgIQRunningNumber'] = 0;

		if ( !isset( $vars['smwgNamespace'] ) ) {
			$vars['smwgNamespace'] = parse_url( $vars['wgServer'], PHP_URL_HOST );
		}

		foreach ( $vars['smwgResourceLoaderDefFiles'] as $key => $file ) {
			if ( is_readable( $file ) ) {
				$vars['wgResourceModules'] = array_merge( $vars['wgResourceModules'], include( $file ) );
			}
		}

		// #3626
		//
		// Required due to support of LTS (1.31)
		// Do replace `mediawiki.api.parse` (Resources.php) with `mediawiki.api`
		// starting with the next supported LTS (likely MW 1.35)
		if ( version_compare( MW_VERSION, '1.32', '>=' ) ) {
			$vars['wgResourceModules']['mediawiki.api.parse'] = [
				'dependencies' => 'mediawiki.api',
				'targets' => [ 'desktop', 'mobile' ]
			];
		}
	}

	private function initConnectionProviders() {
		$applicationFactory = ApplicationFactory::getInstance();

		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();
		$connectionManager = $applicationFactory->getConnectionManager();

		$connectionManager->registerConnectionProvider(
			DB_PRIMARY,
			$mwCollaboratorFactory->newLoadBalancerConnectionProvider( DB_PRIMARY )
		);

		$connectionManager->registerConnectionProvider(
			DB_REPLICA,
			$mwCollaboratorFactory->newLoadBalancerConnectionProvider( DB_REPLICA )
		);

		$connectionManager->registerConnectionProvider(
			'mw.db',
			$mwCollaboratorFactory->newConnectionProvider( 'mw.db' )
		);

		// Connection can be used to redirect queries to another DB cluster
		$connectionManager->registerConnectionProvider(
			'mw.db.queryengine',
			$mwCollaboratorFactory->newConnectionProvider( 'mw.db.queryengine' )
		);

		$connectionManager->registerConnectionProvider(
			'elastic',
			$applicationFactory->singleton( 'ElasticFactory' )->newConnectionProvider()
		);
	}

	private function initMessageCallbackHandler() {
		Message::registerCallbackHandler( Message::TEXT, function ( $arguments, $language ) {
			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			return call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language )->text();
		} );

		Message::registerCallbackHandler( Message::ESCAPED, function ( $arguments, $language ) {
			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			return call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language )->escaped();
		} );

		Message::registerCallbackHandler( Message::PARSE, function ( $arguments, $language ) {
			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			$message = call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language );

			// 1.27+
			// [GlobalTitleFail] MessageCache::parse called by ...
			// Message::parseText/MessageCache::parse with no title set.
			//
			// Message::setInterfaceMessageFlag "... used to restore the flag
			// after setting a language"
			$title = $GLOBALS['wgTitle'] ?? \Title::newFromText( 'Blank', NS_SPECIAL );

			return $message->setInterfaceMessageFlag( true )->title( $title )->parse();
		} );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgJobClasses
	 */
	private function registerJobClasses( &$vars ) {
		$jobClasses = [

			'smw.update' => 'SMW\MediaWiki\Jobs\UpdateJob',
			'smw.refresh' => 'SMW\MediaWiki\Jobs\RefreshJob',
			'smw.updateDispatcher' => 'SMW\MediaWiki\Jobs\UpdateDispatcherJob',
			'smw.fulltextSearchTableUpdate' => 'SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob',
			'smw.entityIdDisposer' => 'SMW\MediaWiki\Jobs\EntityIdDisposerJob',
			'smw.propertyStatisticsRebuild' => 'SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob',
			'smw.fulltextSearchTableRebuild' => 'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			'smw.changePropagationDispatch' => 'SMW\MediaWiki\Jobs\ChangePropagationDispatchJob',
			'smw.changePropagationUpdate' => 'SMW\MediaWiki\Jobs\ChangePropagationUpdateJob',
			'smw.changePropagationClassUpdate' => 'SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob',
			'smw.deferredConstraintCheckUpdateJob' => 'SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob',
			'smw.elasticIndexerRecovery' => 'SMW\Elastic\Jobs\IndexerRecoveryJob',
			'smw.elasticFileIngest' => 'SMW\Elastic\Jobs\FileIngestJob',
			'smw.parserCachePurgeJob' => 'SMW\MediaWiki\Jobs\ParserCachePurgeJob',

			// Legacy 3.0-
			'SMW\UpdateJob' => 'SMW\MediaWiki\Jobs\UpdateJob',
			'SMW\RefreshJob' => 'SMW\MediaWiki\Jobs\RefreshJob',
			'SMW\UpdateDispatcherJob' => 'SMW\MediaWiki\Jobs\UpdateDispatcherJob',
			'SMW\FulltextSearchTableUpdateJob' => 'SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob',
			'SMW\EntityIdDisposerJob' => 'SMW\MediaWiki\Jobs\EntityIdDisposerJob',
			'SMW\PropertyStatisticsRebuildJob' => 'SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob',
			'SMW\FulltextSearchTableRebuildJob' => 'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			'SMW\ChangePropagationDispatchJob' => 'SMW\MediaWiki\Jobs\ChangePropagationDispatchJob',
			'SMW\ChangePropagationUpdateJob' => 'SMW\MediaWiki\Jobs\ChangePropagationUpdateJob',
			'SMW\ChangePropagationClassUpdateJob' => 'SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob',

			// Legacy 2.0-
			'SMWUpdateJob'  => 'SMW\MediaWiki\Jobs\UpdateJob',
			'SMWRefreshJob' => 'SMW\MediaWiki\Jobs\RefreshJob'
		];

		foreach ( $jobClasses as $job => $class ) {
			$vars['wgJobClasses'][$job] = $class;
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgAvailableRights
	 * @see https://www.mediawiki.org/wiki/Manual:$wgGroupPermissions
	 */
	private function registerPermissions( &$vars ) {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		if ( !defined( 'SMW_EXTENSION_LOADED' ) ) {
			return;
		}

		$groupPermissions = new GroupPermissions();

		$groupPermissions->setHookDispatcher(
			$this->hookDispatcher
		);

		$groupPermissions->initPermissions( $vars );

		// Add an additional protection level restricting edit/move/etc
		if ( ( $editProtectionRight = $settings->get( 'smwgEditProtectionRight' ) ) !== false ) {
			$vars['wgRestrictionLevels'][] = $editProtectionRight;
		}
	}

	private function registerParamDefinitions( &$vars ) {
		$vars['wgParamDefinitions']['smwformat'] = [
			'definition'=> '\SMW\Query\ResultFormat',
		];
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgFooterIcons
	 */
	private function registerFooterIcon( &$vars, $path ) {
		if ( !defined( 'SMW_EXTENSION_LOADED' ) ) {
			return;
		}

		if ( isset( $vars['wgFooterIcons']['poweredby']['semanticmediawiki'] ) ) {
			return;
		}

		$vars['wgFooterIcons']['poweredby']['semanticmediawiki'] = [
			'src' => Logo::get( 'footer' ),
			'url' => 'https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki',
			'alt' => 'Powered by Semantic MediaWiki',
			'class' => 'smw-footer'
		];
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgHooks
	 *
	 * @note $wgHooks contains a list of hooks which specifies for every event an
	 * array of functions to be called.
	 */
	private function registerHooks( &$vars ) {
		$hooks = new Hooks();
		$hooks->register( $vars );
	}

}
