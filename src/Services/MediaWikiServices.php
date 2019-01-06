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
use WikiImporter;

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
	 * JobQueueGroup
	 *
	 * @return callable
	 */
	'JobQueueGroup' => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType( 'JobQueueGroup', '\JobQueueGroup' );

		return JobQueueGroup::singleton();
	},

];
