<?php

namespace SMW\Services;

use ImportSource;
use ImportStreamSource;
use ImportStringSource;
use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Language\Language;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\MagicWordFactory;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserCache;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use Onoi\CallbackContainer\CallbackContainerBuilder;
use Psr\Log\LoggerInterface;
use SearchEngineConfig;
use SMW\MediaWiki\FileRepoFinder;
use SMW\MediaWiki\PermissionManager;
use SMW\Utils\Logger;
use WikiImporter;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use WikiPage;

/**
 * @codeCoverageIgnore
 *
 * Services defined in this file SHOULD only be accessed either via the
 * ApplicationFactory or a different factory instance.
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
return [

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 * @param ImportStringSource $source
	 *
	 * @return ImportStringSource
	 */
	'ImportStringSource' => static function ( $containerBuilder, $source ): ImportStringSource {
		$containerBuilder->registerExpectedReturnType( 'ImportStringSource', '\ImportStringSource' );
		return new ImportStringSource( $source );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 * @param ImportStreamSource $source
	 *
	 * @return ImportStreamSource
	 */
	'ImportStreamSource' => static function ( $containerBuilder, $source ): ImportStreamSource {
		$containerBuilder->registerExpectedReturnType( 'ImportStreamSource', '\ImportStreamSource' );
		return new ImportStreamSource( $source );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 * @param ImportSource $importSource
	 *
	 * @return WikiImporter
	 */
	'WikiImporter' => static function ( $containerBuilder, ImportSource $importSource ): WikiImporter {
		$services = MediaWikiServices::getInstance();
		return $services->getWikiImporterFactory()->getWikiImporter(
			$importSource,
			RequestContext::getMain()->getAuthority()
		);
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 * @param Title $title
	 *
	 * @return WikiPage
	 */
	'WikiPage' => static function ( $containerBuilder, Title $title ) {
		$containerBuilder->registerExpectedReturnType( 'WikiPage', '\WikiPage' );
		return ServicesFactory::getInstance()->newPageCreator()->createPage( $title );
	},

	/**
	 * @return Config
	 */
	'MainConfig' => static function (): Config {
		return MediaWikiServices::getInstance()->getMainConfig();
	},

	/**
	 * @return SearchEngineConfig
	 */
	'SearchEngineConfig' => static function (): SearchEngineConfig {
		return MediaWikiServices::getInstance()->getSearchEngineConfig();
	},

	/**
	 * @return MagicWordFactory
	 */
	'MagicWordFactory' => static function (): MagicWordFactory {
		return MediaWikiServices::getInstance()->getMagicWordFactory();
	},

	/**
	 * @return PermissionManager
	 */
	'PermissionManager' => static function (): PermissionManager {
		return new PermissionManager( MediaWikiServices::getInstance()->getPermissionManager() );
	},

	/**
	 * @return LBFactory
	 */
	'DBLoadBalancerFactory' => static function (): LBFactory {
		return MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
	},

	/**
	 * @return ILoadBalancer
	 */
	'DBLoadBalancer' => static function (): ILoadBalancer {
		return MediaWikiServices::getInstance()->getDBLoadBalancer();
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 * @param IConnectionProvider $dbProvider
	 *
	 * @return string
	 */
	'DefaultSearchEngineTypeForDB' => static function ( $containerBuilder, $dbProvider ) {
		return MediaWikiServices::getInstance()->getSearchEngineFactory()->getSearchEngineClass( $dbProvider );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 * @param string $channel
	 * @param string $role
	 *
	 * @return Logger
	 */
	'MediaWikiLogger' => static function ( $containerBuilder, $channel = 'smw', $role = Logger::ROLE_DEVELOPER ): Logger {
		$containerBuilder->registerExpectedReturnType( 'MediaWikiLogger', LoggerInterface::class );

		$logger = LoggerFactory::getInstance( $channel );

		return new Logger( $logger, $role );
	},

	/**
	 * @return FileRepoFinder
	 */
	'FileRepoFinder' => static function (): FileRepoFinder {
		$repoGroup = MediaWikiServices::getInstance()->getRepoGroup();

		return new FileRepoFinder( $repoGroup );
	},

	/**
	 * @param CallbackContainerBuilder $containerBuilder
	 *
	 * @return JobQueueGroup
	 */
	'JobQueueGroup' => static function ( $containerBuilder ): JobQueueGroup {
		$containerBuilder->registerExpectedReturnType( 'JobQueueGroup', '\JobQueueGroup' );

		return MediaWikiServices::getInstance()->getJobQueueGroup();
	},

	/**
	 * @return Parser
	 */
	'Parser' => static function (): Parser {
		return MediaWikiServices::getInstance()->getParser();
	},

	/**
	 * @return Language
	 */
	'ContentLanguage' => static function (): Language {
		return MediaWikiServices::getInstance()->getContentLanguage();
	},

	/**
	 * @return RevisionLookup
	 */
	'RevisionLookup' => static function (): RevisionLookup {
		return MediaWikiServices::getInstance()->getRevisionLookup();
	},

	/**
	 * @return ParserCache
	 */
	'ParserCache' => static function (): ParserCache {
		return MediaWikiServices::getInstance()->getParserCache();
	},

	'UserOptionsLookup' => static function (): UserOptionsLookup {
		return MediaWikiServices::getInstance()->getUserOptionsLookup();
	}

];
