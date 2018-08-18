<?php

namespace SMW\Elastic\Indexer;

use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMWDITime as DITime;
use SMW\DIProperty;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ReplicationStatus {

	/**
	 * @var ElasticClient
	 */
	private $elasticClient;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $elasticClient
	 */
	public function __construct( ElasticClient $connection ) {
		$this->connection = $connection;
		$this->fieldMapper = new FieldMapper();
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function get( $key ) {

		if ( !is_callable( [ $this, $key ] ) ) {
			throw new RuntimeException( "`$key` as accessor is unknown!" );
		}

		return $this->{$key}();
	}

	/**
	 * @since 3.0
	 */
	private function refresh_interval() {

		$refresh_interval = null;

		$settings = $this->connection->getSettings(
			[
				'index' => $this->connection->getIndexName( ElasticClient::TYPE_DATA )
			]
		);

		foreach ( $settings as $key => $value ) {
			if ( isset( $value['settings']['index']['refresh_interval'] ) ) {
				$refresh_interval = $value['settings']['index']['refresh_interval'];
			}
		}

		return $refresh_interval;
	}

	/**
	 * @since 3.0
	 */
	private function last_update() {

		$pid = $this->fieldMapper->getPID( \SMWSql3SmwIds::$special_ids['_MDAT'] );
		$field = $this->fieldMapper->getField( new DIProperty( '_MDAT' ) );

		$params = $this->fieldMapper->exists( "$pid.$field" );

		$body = [
			'_source' => [ "$pid.$field", "subject" ],
			'size'    => 1,
			'query'   => $params,
			'sort'    => [ "$pid.$field" => [ 'order' => 'desc' ] ]
		];

		$params = [
			'index' => $this->connection->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'body'  => $body
		];

		list( $res, $errors ) = $this->connection->search( $params );
		$time = null;

		foreach ( $res as $result ) {

			if ( !isset( $result['hits'] ) ) {
				continue;
			}

			foreach ( $result['hits'] as $key => $value ) {
				foreach ( $value as $key => $v ) {
					if ( $key === '_source' ) {
						$time = DITime::newFromJD( end( $v[$pid][$field] ) );
					}
				}
			}
		}

		if ( $time !== null ) {
			$time = $time->asDateTime()->format( 'Y-m-d H:i:s' );
		} else {
			$time = '0000-00-00 00:00:00';
		}

		return $time;
	}

}
