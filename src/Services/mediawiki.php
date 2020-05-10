<?php

namespace SMW\Services;

use ImportStreamSource;
use ImportStringSource;
use JobQueueGroup;
use LBFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\NullLogger;
use SMW\Utils\Logger;
use SMW\MediaWiki\NamespaceInfo;
use SMW\MediaWiki\FileRepoFinder;
use SMW\MediaWiki\PermissionManager;
use WikiImporter;
use RepoGroup;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed either via the
 * ApplicationFactory or a different factory instance.
 *
 * @license GNU GPL v2
 * @since 2.5
 *
 * @author mwjames
 */
return [

	/**
	 * ImportStringSource
	 *
	 * @return callable
	 */
	'ImportStringSource' => function( $containerBuilder, $source ) {
		$containerBuilder->registerExpectedReturnType( 'ImportStringSource', '\ImportStringSource' );
		return new ImportStringSource( $source );
	},

	/**
	 * ImportStreamSource
	 *
	 * @return callable
	 */
	'ImportStreamSource' => function( $containerBuilder, $source ) {
		$containerBuilder->registerExpectedReturnType( 'ImportStreamSource', '\ImportStreamSource' );
		return new ImportStreamSource( $source );
	},

	/**
	 * WikiImporter
	 *
	 * @return callable
	 */
	'WikiImporter' => function( $containerBuilder, \ImportSource $importSource ) {
		$containerBuilder->registerExpectedReturnType( 'WikiImporter', '\WikiImporter' );
		return new WikiImporter( $importSource, $containerBuilder->create( 'MainConfig' ) );
	},

	/**
	 * WikiPage
	 *
	 * @return callable
	 */
	'WikiPage' => function( $containerBuilder, \Title $title ) {
		$containerBuilder->registerExpectedReturnType( 'WikiPage', '\WikiPage' );
		return \WikiPage::factory( $title );
	},

	/**
	 * ResourceLoader
	 *
	 * @return callable
	 */
	'ResourceLoader' => function( $containerBuilder ) {

		// #3916
		// > MW 1.33
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getResourceLoader' ) ) {
			return MediaWikiServices::getInstance()->getResourceLoader();
		}

		return new \ResourceLoader();
	},

	/**
	 * Config
	 *
	 * @return callable
	 */
	'MainConfig' => function( $containerBuilder ) {

		// > MW 1.27
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getMainConfig' ) ) {
			return MediaWikiServices::getInstance()->getMainConfig();
		}

		return \ConfigFactory::getDefaultInstance()->makeConfig( 'main' );
	},

	/**
	 * SearchEngineConfig
	 *
	 * @return callable
	 */
	'SearchEngineConfig' => function( $containerBuilder ) {

		// > MW 1.27
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getSearchEngineConfig' ) ) {
			return MediaWikiServices::getInstance()->getSearchEngineConfig();
		}

		return null;
	},

	/**
	 * MagicWordFactory
	 *
	 * @return callable
	 */
	'MagicWordFactory' => function( $containerBuilder ) {

		// > MW 1.32
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getMagicWordFactory' ) ) {
			return MediaWikiServices::getInstance()->getMagicWordFactory();
		}

		return null;
	},

	/**
	 * PermissionManager
	 *
	 * @return callable
	 */
	'PermissionManager' => function( $containerBuilder ) {

		$permissionManager = null;

		// > MW 1.33
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getPermissionManager' ) ) {
			$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		}

		return new PermissionManager( $permissionManager );
	},

	/**
	 * LBFactory
	 *
	 * @return callable
	 */
	'DBLoadBalancerFactory' => function( $containerBuilder ) {

		if ( class_exists( '\Wikimedia\Rdbms\LBFactory' ) ) {
			$containerBuilder->registerExpectedReturnType( 'DBLoadBalancerFactory', '\Wikimedia\Rdbms\LBFactory' );
		} else {
			$containerBuilder->registerExpectedReturnType( 'DBLoadBalancerFactory', '\LBFactory' );
		}

		// > MW 1.28
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getDBLoadBalancerFactory' ) ) {
			return MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		}

		return LBFactory::singleton();
	},

	/**
	 * DBLoadBalancer
	 *
	 * @return callable
	 */
	'DBLoadBalancer' => function( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'DBLoadBalancer', '\LoadBalancer' );

		// > MW 1.27
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getDBLoadBalancer' ) ) {
			return MediaWikiServices::getInstance()->getDBLoadBalancer();
		}

		return LBFactory::singleton()->getMainLB();
	},

	/**
	 * DBLoadBalancer
	 *
	 * @return callable
	 */
	'DefaultSearchEngineTypeForDB' => function( $containerBuilder, \IDatabase $db ) {

		// MW > 1.27
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( 'SearchEngineFactory', 'getSearchEngineClass' ) ) {
			return MediaWikiServices::getInstance()->getSearchEngineFactory()->getSearchEngineClass( $db );
		}

		return $db->getSearchEngine();
	},

	/**
	 * MediaWikiLogger
	 *
	 * @return callable
	 */
	'MediaWikiLogger' => function( $containerBuilder, $channel = 'smw', $role = Logger::ROLE_DEVELOPER ) {

		$containerBuilder->registerExpectedReturnType( 'MediaWikiLogger', '\Psr\Log\LoggerInterface' );

		if ( class_exists( '\MediaWiki\Logger\LoggerFactory' ) ) {
			$logger = LoggerFactory::getInstance( $channel );
		} else {
			$logger = new NullLogger();
		}

		return new Logger( $logger, $role );
	},

	/**
	 * NamespaceInfo
	 *
	 * @return callable
	 */
	'NamespaceInfo' => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType( 'NamespaceInfo', '\SMW\MediaWiki\NamespaceInfo' );
		$namespaceInfo = null;

		// MW > 1.33
		// https://github.com/wikimedia/mediawiki/commit/76661cf129e0dea40edefbd7d35a3f09130572a1
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getNamespaceInfo' ) ) {
			$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
		}

		return new NamespaceInfo( $namespaceInfo );
	},

	/**
	 * RepoGroup
	 *
	 * @return callable
	 */
	'FileRepoFinder' => function( $containerBuilder ) {

		$repoGroup = null;

		// MW > 1.34
		// https://github.com/wikimedia/mediawiki/commit/21e2d71560cb87191dd80ae0750d0190b45063c1
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getRepoGroup' ) ) {
			$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();
		} else {
			$repoGroup = RepoGroup::singleton();
		}

		return new FileRepoFinder( $repoGroup );
	},

	/**
	 * JobQueueGroup
	 *
	 * @return callable
	 */
	'JobQueueGroup' => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType( 'JobQueueGroup', '\JobQueueGroup' );

		return JobQueueGroup::singleton();
	},

	/**
	 * Parser
	 *
	 * @return callable
	 */
	'Parser' => function( $containerBuilder ) {

		// MW 1.34+
		// https://github.com/wikimedia/mediawiki/commit/e6df285854622144df973764af908d34b4befbe9#diff-3352fb15886da832b6b01b6f5023eb00
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getParser' ) ) {
			return MediaWikiServices::getInstance()->getParser();
		}

		return $GLOBALS['wgParser'];
	},

	/**
	 * ContentLanguage
	 *
	 * @return callable
	 */
	'ContentLanguage' => function( $containerBuilder ) {

		// MW 1.35+
		// https://phabricator.wikimedia.org/T245940
		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getContentLanguage' ) ) {
			return MediaWikiServices::getInstance()->getContentLanguage();
		}

		return $GLOBALS['wgContLang'];
	},

	/**
	 * RevisionLookup
	 *
	 * @return callable
	 */
	'RevisionLookup' => function( $containerBuilder ) {

		if ( class_exists( '\MediaWiki\MediaWikiServices' ) && method_exists( '\MediaWiki\MediaWikiServices', 'getRevisionLookup' ) ) {
			return MediaWikiServices::getInstance()->getRevisionLookup();
		}

		return null;
	},

	/**
	 * ParserCache
	 *
	 * @return callable
	 */
	'ParserCache' => function( $containerBuilder ) {
		return MediaWikiServices::getInstance()->getParserCache();
	},

];
