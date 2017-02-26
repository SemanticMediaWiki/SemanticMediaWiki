<?php

namespace SMW\Services;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use LBFactory;
use Psr\Log\NullLogger;

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
return array(

	/**
	 * WikiPage
	 *
	 * @return callable
	 */
	'WikiPage' => function( $containerBuilder, \Title $title  ) {
		$containerBuilder->registerExpectedReturnType( 'WikiPage', '\WikiPage' );
		return \WikiPage::factory( $title );
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
	'MediaWikiLogger' => function( $containerBuilder ) {

		$containerBuilder->registerExpectedReturnType( 'MediaWikiLogger', '\Psr\Log\LoggerInterface' );

		if ( class_exists( '\MediaWiki\Logger\LoggerFactory' ) ) {
			return LoggerFactory::getInstance( 'smw' );
		}

		return new NullLogger();
	},

);
