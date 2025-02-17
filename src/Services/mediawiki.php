<?php

namespace SMW\Services;

use ImportStreamSource;
use ImportStringSource;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserOptionsLookup;
use RequestContext;
use SMW\MediaWiki\FileRepoFinder;
use SMW\MediaWiki\NamespaceInfo;
use SMW\MediaWiki\PermissionManager;
use SMW\Utils\Logger;

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
	'ImportStringSource' => static function ( $containerBuilder, $source ) {
		$containerBuilder->registerExpectedReturnType( 'ImportStringSource', '\ImportStringSource' );
		return new ImportStringSource( $source );
	},

	/**
	 * ImportStreamSource
	 *
	 * @return callable
	 */
	'ImportStreamSource' => static function ( $containerBuilder, $source ) {
		$containerBuilder->registerExpectedReturnType( 'ImportStreamSource', '\ImportStreamSource' );
		return new ImportStreamSource( $source );
	},

	/**
	 * WikiImporter
	 *
	 * @return callable
	 */
	'WikiImporter' => static function ( $containerBuilder, \ImportSource $importSource ) {
		$services = MediaWikiServices::getInstance();
		return $services->getWikiImporterFactory()->getWikiImporter(
			$importSource,
			RequestContext::getMain()->getAuthority()
		);
	},

	/**
	 * WikiPage
	 *
	 * @return callable
	 */
	'WikiPage' => static function ( $containerBuilder, \Title $title ) {
		$containerBuilder->registerExpectedReturnType( 'WikiPage', '\WikiPage' );
		return ServicesFactory::getInstance()->newPageCreator()->createPage( $title );
	},

	/**
	 * Config
	 *
	 * @return callable
	 */
	'MainConfig' => static function () {
		return MediaWikiServices::getInstance()->getMainConfig();
	},

	/**
	 * SearchEngineConfig
	 *
	 * @return callable
	 */
	'SearchEngineConfig' => static function () {
		return MediaWikiServices::getInstance()->getSearchEngineConfig();
	},

	/**
	 * MagicWordFactory
	 *
	 * @return callable
	 */
	'MagicWordFactory' => static function () {
		return MediaWikiServices::getInstance()->getMagicWordFactory();
	},

	/**
	 * PermissionManager
	 *
	 * @return callable
	 */
	'PermissionManager' => static function () {
		return new PermissionManager( MediaWikiServices::getInstance()->getPermissionManager() );
	},

	/**
	 * LBFactory
	 *
	 * @return callable
	 */
	'DBLoadBalancerFactory' => static function () {
		return MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
	},

	/**
	 * DBLoadBalancer
	 *
	 * @return callable
	 */
	'DBLoadBalancer' => static function () {
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
	'DefaultSearchEngineTypeForDB' => static function ( $containerBuilder, $dbProviderOrdbOrLb ) {
		return MediaWikiServices::getInstance()->getSearchEngineFactory()->getSearchEngineClass( $dbProviderOrdbOrLb );
	},

	/**
	 * MediaWikiLogger
	 *
	 * @return callable
	 */
	'MediaWikiLogger' => static function ( $containerBuilder, $channel = 'smw', $role = Logger::ROLE_DEVELOPER ) {
		$containerBuilder->registerExpectedReturnType( 'MediaWikiLogger', '\Psr\Log\LoggerInterface' );

		$logger = LoggerFactory::getInstance( $channel );

		return new Logger( $logger, $role );
	},

	/**
	 * NamespaceInfo
	 *
	 * @return callable
	 */
	'NamespaceInfo' => static function ( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'NamespaceInfo', '\SMW\MediaWiki\NamespaceInfo' );
		$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();

		return new NamespaceInfo( $namespaceInfo );
	},

	/**
	 * RepoGroup
	 *
	 * @return callable
	 */
	'FileRepoFinder' => static function () {
		$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();

		return new FileRepoFinder( $repoGroup );
	},

	/**
	 * JobQueueGroup
	 *
	 * @return callable
	 */
	'JobQueueGroup' => static function ( $containerBuilder ) {
		$containerBuilder->registerExpectedReturnType( 'JobQueueGroup', '\JobQueueGroup' );

		return MediaWikiServices::getInstance()->getJobQueueGroup();
	},

	/**
	 * Parser
	 *
	 * @return callable
	 */
	'Parser' => static function () {
		return MediaWikiServices::getInstance()->getParser();
	},

	/**
	 * ContentLanguage
	 *
	 * @return callable
	 */
	'ContentLanguage' => static function () {
		return MediaWikiServices::getInstance()->getContentLanguage();
	},

	/**
	 * RevisionLookup
	 *
	 * @return callable
	 */
	'RevisionLookup' => static function () {
		return MediaWikiServices::getInstance()->getRevisionLookup();
	},

	/**
	 * ParserCache
	 *
	 * @return callable
	 */
	'ParserCache' => static function () {
		return MediaWikiServices::getInstance()->getParserCache();
	},

	'UserOptionsLookup' => static function (): UserOptionsLookup {
		return MediaWikiServices::getInstance()->getUserOptionsLookup();
	}

];
