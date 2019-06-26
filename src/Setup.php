<?php

namespace SMW;

use SMW\Connection\ConnectionManager;
use SMW\MediaWiki\Hooks;
use SMW\Utils\AbortMessage;

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
	 * Runs at the earliest possible event to initialize functions or hooks that
	 * are otherwise too late for the hook system to be recognized.
	 *
	 * @since 3.0
	 */
	public static function initExtension( &$vars ) {
		Hooks::registerEarly( $vars );
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
	 * @since 3.0
	 *
	 * @param array &$vars
	 */
	public function loadSchema( &$vars ) {
		SetupFile::loadSchema( $vars );
	}

	/**
	 * @since 1.9
	 *
	 * @param array &$vars
	 * @param string $localDirectory
	 */
	public function init( &$vars, $localDirectory ) {

		$this->initMessageCallbackHandler();

		$setupCheck = new SetupCheck(
			[
				'version' => SMW_VERSION,
				'smwgUpgradeKey' => $vars['smwgUpgradeKey'],
				'wgScriptPath' => $vars['wgScriptPath']
			]
		);

		if ( $setupCheck->hasError( $vars ) === false ) {
			$setupCheck->triggerErrorAndAbort( $setupCheck->isCli(), $vars );
		}

		$this->addDefaultConfigurations( $vars );

		if ( CompatibilityMode::extensionNotEnabled() ) {
			CompatibilityMode::disableSemantics();
		}

		$this->initConnectionProviders( );

		$this->registerJobClasses( $vars );
		$this->registerPermissions( $vars );

		$this->registerParamDefinitions( $vars );
		$this->registerFooterIcon( $vars, $localDirectory );
		$this->registerHooks( $vars, $localDirectory );

		\Hooks::run( 'SMW::Setup::AfterInitializationComplete', [ &$vars ] );
	}

	private function addDefaultConfigurations( &$vars ) {

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
	}

	private function initConnectionProviders() {

		$applicationFactory = ApplicationFactory::getInstance();

		$mwCollaboratorFactory = $applicationFactory->newMwCollaboratorFactory();
		$connectionManager = $applicationFactory->getConnectionManager();

		$connectionManager->registerConnectionProvider(
			DB_MASTER,
			$mwCollaboratorFactory->newLoadBalancerConnectionProvider( DB_MASTER )
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

		$src = '';

		if ( is_file( $path . '/res/DataURI.php' ) && ( $dataURI = include $path . '/res/DataURI.php' ) !== [] ) {
			$src = $dataURI['footer'];
		}

		$vars['wgFooterIcons']['poweredby']['semanticmediawiki'] = [
			'src' => $src,
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
