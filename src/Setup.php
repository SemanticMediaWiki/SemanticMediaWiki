<?php

namespace SMW;

use SMW\Connection\ConnectionManager;
use SMW\MediaWiki\Hooks;
use SMW\Utils\Logo;

/**
 * Extension setup and registration
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
final class Setup {

	/**
	 * Registers a hook even before the "early" registration to allow checking
	 * whether the extension is loaded and enabled.
	 *
	 * @since 3.1
	 *
	 * @param array $vars
	 */
	public static function checkExtensionRegistration( &$vars ) {

		if ( $vars['smwgIgnoreExtensionRegistrationCheck'] ) {
			return;
		}

		Hooks::registerExtensionCheck( $vars );
	}

	/**
	 * Runs at the earliest possible event to initialize functions or hooks that
	 * are otherwise too late for the hook system to be recognized.
	 *
	 * @since 3.0
	 */
	public static function initExtension( &$vars ) {
		Hooks::registerEarly( $vars );

		// Register connection providers early to ensure the invocation of SMW
		// related extensions such as `wfLoadExtension( 'SemanticCite' );` can
		// happen before or after `enableSemantics` so that the check by the
		// `ConnectionManager` (#4170) doesn't throw an error when an extension
		// access the `Store` during `onExtensionFunction`
		self::initConnectionProviders();
	}

	/**
	 * @since 3.0
	 */
	public static function isEnabled() {
		return defined( 'SMW_VERSION' ) && $GLOBALS['smwgSemanticsEnabled'];
	}

	/**
	 * @since 3.0
	 */
	public static function isValid( $isCli = false ) {
		return SetupFile::isGoodSchema( $isCli );
	}

	/**
	 * @since 1.9
	 *
	 * @param array &$vars
	 * @param string $rootDir
	 */
	public function init( &$vars, $rootDir ) {

		$setupFile = new SetupFile();
		$setupFile->loadSchema( $vars );

		$this->initMessageCallbackHandler();

		$setupCheck = new SetupCheck(
			[
				'version' => SMW_VERSION,
				'smwgUpgradeKey' => $vars['smwgUpgradeKey'],
				'wgScriptPath' => $vars['wgScriptPath']
			],
			$setupFile
		);

		if ( $setupCheck->hasError() ) {
			$setupCheck->showErrorAndAbort( $setupCheck->isCli() );
		}

		$this->addDefaultConfigurations( $vars, $rootDir );

		if ( CompatibilityMode::extensionNotEnabled() ) {
			CompatibilityMode::disableSemantics();
		}

		$this->registerJobClasses( $vars );
		$this->registerPermissions( $vars );

		$this->registerParamDefinitions( $vars );
		$this->registerFooterIcon( $vars, $rootDir );
		$this->registerHooks( $vars, $rootDir );

		\Hooks::run( 'SMW::Setup::AfterInitializationComplete', [ &$vars ] );
	}

	private function addDefaultConfigurations( &$vars, $rootDir ) {

		// Convenience function for extensions depending on a SMW specific
		// test infrastructure
		if ( !defined( 'SMW_PHPUNIT_AUTOLOADER_FILE' ) ) {
			define( 'SMW_PHPUNIT_AUTOLOADER_FILE', "$rootDir/tests/autoloader.php" );
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
				$vars['wgResourceModules'] = array_merge( $vars['wgResourceModules'], include ( $file ) );
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

	private static function initConnectionProviders() {

		$applicationFactory = ApplicationFactory::getInstance();

		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();
		$connectionManager = $applicationFactory->getConnectionManager();

		$connectionManager->registerConnectionProvider(
			DB_MASTER,
			$mwCollaboratorFactory->newLoadBalancerConnectionProvider( DB_MASTER )
		);

		$connectionManager->registerConnectionProvider(
			DB_REPLICA,
			$mwCollaboratorFactory->newLoadBalancerConnectionProvider( DB_REPLICA, false )
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

		Message::registerCallbackHandler( Message::TEXT, function( $arguments, $language ) {

			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			return call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language )->text();
		} );

		Message::registerCallbackHandler( Message::ESCAPED, function( $arguments, $language ) {

			if ( $language === Message::CONTENT_LANGUAGE ) {
				$language = Localizer::getInstance()->getContentLanguage();
			}

			if ( $language === Message::USER_LANGUAGE ) {
				$language = Localizer::getInstance()->getUserLanguage();
			}

			return call_user_func_array( 'wfMessage', $arguments )->inLanguage( $language )->escaped();
		} );

		Message::registerCallbackHandler( Message::PARSE, function( $arguments, $language ) {

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
			return $message->setInterfaceMessageFlag( true )->title( $GLOBALS['wgTitle'] )->parse();
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
			'smw.elasticIndexerRecovery' => 'SMW\Elastic\Indexer\IndexerRecoveryJob',
			'smw.elasticFileIngest' => 'SMW\Elastic\Indexer\FileIngestJob',
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

		if ( !$settings->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		$rights = [
			'smw-admin' => [
				'sysop',
				'smwadministrator'
			],
			'smw-patternedit' => [
				'smwcurator'
			],
			'smw-schemaedit' => [
				'smwcurator'
			],
			'smw-pageedit' => [
				'smwcurator'
			],
		//	'smw-watchlist' => [
		//		'smwcurator'
		//	],
		];

		foreach ( $rights as $right => $roles ) {

			// Rights
			$vars['wgAvailableRights'][] = $right;

			// User group rights
			foreach ( $roles as $role ) {
				if ( !isset( $vars['wgGroupPermissions'][$role][$right] ) ) {
					$vars['wgGroupPermissions'][$role][$right] = true;
				}
			}
		}

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

		$applicationFactory = ApplicationFactory::getInstance();

		if ( !$applicationFactory->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			return;
		}

		if( isset( $vars['wgFooterIcons']['poweredby']['semanticmediawiki'] ) ) {
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
	private function registerHooks( &$vars, $localDirectory ) {
		$hooks = new Hooks( $localDirectory );
		$hooks->register( $vars );
	}

}
