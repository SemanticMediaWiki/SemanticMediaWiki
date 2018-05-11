<?php

namespace SMW\Elastic;

use SMW\ApplicationFactory;
use SMW\Store;
use SMW\Options;
use SMW\DIWikiPage;
use Psr\Log\LoggerInterface;
use SMW\SQLStore\PropertyTableRowMapper;
use SMW\Elastic\QueryEngine\QueryEngine;
use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Elastic\QueryEngine\TermsLookup\TermsLookup;
use SMW\Elastic\QueryEngine\TermsLookup\CachingTermsLookup;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Indexer\Rebuilder;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Admin\SettingsInfoProvider;
use SMW\Elastic\Admin\IndicesInfoProvider;
use SMW\Elastic\Admin\MappingsInfoProvider;
use SMW\Elastic\Admin\NodesInfoProvider;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticFactory {

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param MessageReporter|null $messageReporter
	 * @param LoggerInterface|null $logger
	 *
	 * @return Indexer
	 */
	public function newIndexer( Store $store, MessageReporter $messageReporter = null, LoggerInterface $logger = null ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$indexer = new Indexer( $store );

		if ( $logger === null ) {
			$logger = $applicationFactory->getMediaWikiLogger( 'smw-elastic' );
		}

		if ( $messageReporter === null ) {
			$messageReporter = new NullMessageReporter();
		}

		$indexer->setLogger(
			$logger
		);

		$indexer->setMessageReporter(
			$messageReporter
		);

		return $indexer;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param Options $options
	 *
	 * @return QueryEngine
	 */
	public function newQueryEngine( Store $store, Options $options = null ) {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( $options === null ) {
			$options = $store->getConnection( 'elastic' )->getConfig();
		}

		$queryOptions = new Options( $options->safeGet( 'query', [] ) );

		$termsLookup = new CachingTermsLookup(
			new TermsLookup( $store, $queryOptions ),
			$applicationFactory->getCache()
		);

		$queryBuilder = new QueryBuilder(
			$store,
			$termsLookup
		);

		$queryBuilder->setOptions( $queryOptions );

		$queryEngine = new QueryEngine(
			$store,
			$queryBuilder,
			$options
		);

		$queryEngine->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
		);

		return $queryEngine;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return Rebuilder
	 */
	public function newRebuilder( Store $store ) {

		$rebuilder = new Rebuilder(
			$store->getConnection( 'elastic' ),
			$this->newIndexer( $store ),
			new PropertyTableRowMapper( $store )
		);

		return $rebuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return ElasticClientTaskHandler
	 */
	public function newInfoTaskHandler( Store $store, $outputFormatter ) {

		$taskHandlers = [
			new SettingsInfoProvider( $outputFormatter ),
			new MappingsInfoProvider( $outputFormatter ),
			new IndicesInfoProvider( $outputFormatter ),
			new NodesInfoProvider( $outputFormatter )
		];

		return new ElasticClientTaskHandler( $outputFormatter, $taskHandlers );
	}

}
