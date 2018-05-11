<?php

namespace SMW\Elastic\Indexer;

use Psr\Log\LoggerAwareTrait;
use SMW\DIWikiPage;
use SMW\Elastic\Connection\Client as ElasticClient;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use Revision;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TextIndexer {

	use MessageReporterAwareTrait;
	use LoggerAwareTrait;

	/**
	 * @var Indexer
	 */
	private $indexer;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @since 3.0
	 *
	 * @return Indexer $indexer
	 */
	public function __construct( Indexer $indexer ) {
		$this->indexer = $indexer;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $revId
	 *
	 * @return string
	 */
	public function textFromRevID( $id ) {

		if ( $id == 0 ) {
			return '';
		};

		$revision = Revision::newFromId( $id );

		if ( $revision == null ) {
			return '';
		};

		$content = $revision->getContent( Revision::RAW );

		return $content->getNativeData();
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $dataItem
	 * @param string $text
	 */
	public function index( DIWikiPage $dataItem, $text = '' ) {

		// Fall hard to know that someone tried an index process without an ID!
		if ( $dataItem->getId() == 0 ) {
			throw new RuntimeException( "Missing an ID to index a text!" );
		}

		$time = -microtime( true );

		$index = $this->indexer->getIndexName(
			ElasticClient::TYPE_DATA
		);

		$params = [
			'_index' => $index,
			'_type'  => ElasticClient::TYPE_DATA
 		];

		$bulk = $this->indexer->newBulk( $params );
		$text = $this->indexer->removeLinks( $text );

 		$bulk->upsert(
 			[
 				'_index' => $index,
				'_type'  => ElasticClient::TYPE_DATA,
				'_id'    => $dataItem->getId()
 			],
 			[
				'text_raw' => $text
 			]
 		);

		$response = $bulk->execute();

 		$context = [
 			'method' => __METHOD__,
 			'role' => 'user',
 			'origin' => $this->origin,
			'subject' => $dataItem->getHash(),
			'procTime' => microtime( true ) + $time,
			'response' => $response
		];

		$msg = [
			'Text indexer',
			'Upsert completed ({subject})',
			'procTime (in sec): {procTime}',
			'Response: {response}'
		];

		$this->logger->info( $msg, $context );
	}

}
