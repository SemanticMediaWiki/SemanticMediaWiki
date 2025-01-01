<?php

namespace SMW\Services;

use ImportStreamSource;
use ImportStringSource;
use JobQueueGroup;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserOptionsLookup;
use RequestContext;
use SMW\MediaWiki\FileRepoFinder;
use SMW\MediaWiki\NamespaceInfo;
use SMW\MediaWiki\PermissionManager;
use SMW\Utils\Logger;
use WikiImporter;
use Wikimedia\Rdbms\IDatabase;

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
	'ImportStringSource' => function ( $containerBuilder, $source ) {
		$containerBuilder->registerExpectedReturnType( 'ImportStringSource', '\ImportStringSource' );
		return new ImportStringSource( $source );
	},

	/**
	 * ImportStreamSource
	 *
	 * @return callable
	 */
	'ImportStreamSource' => function ( $containerBuilder, $source ) {
		$containerBuilder->registerExpectedReturnType( 'ImportStreamSource', '\ImportStreamSource' );
		return new ImportStreamSource( $source );
	},

	/**
	 * WikiImporter
	 *
	 * @return callable
	 */
	'WikiImporter' => function ( $containerBuilder, \ImportSource $importSource ) {
		$containerBuilder->registerExpectedReturnType( 'WikiImporter', '\WikiImporter' );
		$services = MediaWikiServices::getInstance();

		// MW 1.41 or lower
		if ( version_compare( MW_VERSION, '1.42', '<' ) ) {
			return new WikiImporter(
				$importSource,
				$containerBuilder->create( 'MainConfig' ),
				$services->getHookContainer(),
				$services->getContentLanguage(),
				$services->getNamespaceInfo(),
				$services->getTitleFactory(),
				$services->getWikiPageFactory(),
				$services->getWikiRevisionUploadImporter(),
				$services->getPermissionManager(),
				$services->getContentHandlerFactory(),
				$services->getSlotRoleRegistry()
			);
		}

		// MW 1.42+
		return new WikiImporter(
			$importSource,
			RequestContext::getMain()->getAuthority(),
			$containerBuilder->create( 'MainConfig' ),
			$services->getHookContainer(),
			$services->getContentLanguage(),
			$services->getNamespaceInfo(),
			$services->getTitleFactory(),
			$services->getWikiPageFactory(),
			$services->getWikiRevisionUploadImporter(),
			$services->getContentHandlerFactory(),
			$services->getSlotRoleRegistry()
		);
	},

	/**
	 * WikiPage
	 *
	 * @return callable
	 */
	'WikiPage' => function ( $containerBuilder, \Title $title ) {
		$containerBuilder->registerExpectedReturnType( 'WikiPage', '\WikiPage' );
		return ServicesFactory::getInstance()->newPageCreator()->createPage( $title );
	},

	/**
	 * Config
	 *
	 * @return callable
	 */
	'MainConfig' => function () {
		return MediaWikiServices::getInstance()->getMainConfig();
	},

	/**
	 * SearchEngineConfig
	 *
	 * @return callable
	 */
	'SearchEngineConfig' => function () {
		return MediaWikiServices::getInstance()->getSearchEngineConfig();
	},

	/**
	 * MagicWordFactory
	 *
	 * @return callable
	 */
	'MagicWordFactory' => function () {
		return MediaWikiServices::getInstance()->getMagicWordFactory();
	},

	/**
	 * PermissionManager
	 *
	 * @return callable
	 */
	'PermissionManager' => function () {
		return new PermissionManager( MediaWikiServices::getInstance()->getPermissionManager() );
	},

	/**
	 * LBFactory
	 *
	 * @return callable
	 */
	'DBLoadBalancerFactory' => function () {
		return MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
	},

	/**
	 * DBLoadBalancer
	 *
	 * @return callable
	 */
	'DBLoadBalancer' => function () {
		return MediaWikiServices::getInstance()->getDBLoadBalancer();
	},

	/**
	 * DBLoadBalancer
	 * $dbProviderOrdbOrLb is:
	 * - IConnectionProvider when MW >= 1.41
	 * - IDatabase | ILoadBalancer when MW < 1.41
	 * https://phabricator.wikimedia.org/T326274
	 *
	 * @return callable
	 */
	'DefaultSearchEngineTypeForDB' => function ( $containerBuilder, $dbProviderOrdbOrLb ) {
		return MediaWikiServices::getInstance()->getSearchEngineFactory()->getSearchEngineClass( $dbProviderOrdbOrLb );
	},

	/**
	 * MediaWikiLogger
	 *
	 * @return callable
	 */
	'MediaWikiLogger' => function ( $containerBuilder, $channel = 'smw', $role = Logger::ROLE_DEVELOPER ) {
		$containerBuilder->registerExpectedReturnType( 'MediaWikiLogger', '\Psr\Log\LoggerInterface' );

		$logger = LoggerFactory::getInstance( $channel );

		return new Logger( $logger, $role );
	},

	/**
	 * NamespaceInfo
	 *
	 * @return callable
	 */
	'NamespaceInfo' => function ( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'NamespaceInfo', '\SMW\MediaWiki\NamespaceInfo' );
		$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();

		return new NamespaceInfo( $namespaceInfo );
	},

	/**
	 * RepoGroup
	 *
	 * @return callable
	 */
	'FileRepoFinder' => function () {
		$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();

		return new FileRepoFinder( $repoGroup );
	},

	/**
	 * JobQueueGroup
	 *
	 * @return callable
	 */
	'JobQueueGroup' => function ( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'JobQueueGroup', '\JobQueueGroup' );

		return MediaWikiServices::getInstance()->getJobQueueGroup();
	},

	/**
	 * Parser
	 *
	 * @return callable
	 */
	'Parser' => function () {
		return MediaWikiServices::getInstance()->getParser();
	},

	/**
	 * ContentLanguage
	 *
	 * @return callable
	 */
	'ContentLanguage' => function () {
		return MediaWikiServices::getInstance()->getContentLanguage();
	},

	/**
	 * RevisionLookup
	 *
	 * @return callable
	 */
	'RevisionLookup' => function () {
		return MediaWikiServices::getInstance()->getRevisionLookup();
	},

	/**
	 * ParserCache
	 *
	 * @return callable
	 */
	'ParserCache' => function () {
		return MediaWikiServices::getInstance()->getParserCache();
	},

	'UserOptionsLookup' => function (): UserOptionsLookup {
		return MediaWikiServices::getInstance()->getUserOptionsLookup();
	}

];
