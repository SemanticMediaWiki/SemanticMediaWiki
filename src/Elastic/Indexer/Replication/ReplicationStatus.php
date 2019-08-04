<?php

namespace SMW\Elastic\Indexer\Replication;

use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMWDITime as DITime;
use SMW\DIProperty;
use SMW\DIWikiPage;
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
	public function get( $key, ...$args ) {

		if ( $key === 'associated_revision' ) {
			$key = 'getAssociatedRev';
		}

		if ( !is_callable( [ $this, $key ] ) ) {
			throw new RuntimeException( "`$key` as accessor is unknown!" );
		}

		return $this->{$key}( ...$args );
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	private function documentExistsById( $id ) {

		$params = [
			'index' => $this->connection->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $id,
		];

		return $this->connection->exists( $params );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 *
	 * @return []
	 */
	private function modification_date_associated_revision( $id ) {

		$params = [
			'index' => $this->connection->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $id,
		];

		if ( !$this->connection->exists( $params ) ) {
			return [ 'modification_date' => false, 'associated_revision' => 0 ];
		}

		$pid = $this->fieldMapper->getPID( \SMW\SQLStore\EntityStore\EntityIdManager::$special_ids['_MDAT'] );
		$field = $this->fieldMapper->getField( new DIProperty( '_MDAT' ) );

		$doc = $this->connection->get( $params + [ '_source_include' => [ "$pid.$field", "subject.rev_id" ] ] );

		if ( isset( $doc['_source'][$pid][$field] ) ) {
			$date = end( $doc['_source'][$pid][$field] );
			$modification_date = DITime::newFromJD( $date, DITime::CM_GREGORIAN, DITime::PREC_YMDT );
		} else {
			$modification_date = false;
		}

		if ( isset( $doc['_source']['subject']['rev_id'] ) ) {
			$associated_revision = $doc['_source']['subject']['rev_id'];
		} else {
			$associated_revision = 0;
		}

		return [ 'modification_date' => $modification_date, 'associated_revision' => $associated_revision ];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 *
	 * @return boolean|DITime
	 * @throws RuntimeException
	 */
	public function getModificationDate( $id ) {

		$params = [
			'index' => $this->connection->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $id,
		];

		if ( !$this->connection->exists( $params ) ) {
			return false;
		}

		$pid = $this->fieldMapper->getPID( \SMW\SQLStore\EntityStore\EntityIdManager::$special_ids['_MDAT'] );
		$field = $this->fieldMapper->getField( new DIProperty( '_MDAT' ) );

		$doc = $this->connection->get( $params + [ '_source_include' => [ "$pid.$field" ] ] );

		if ( !isset( $doc['_source'][$pid][$field] ) ) {
			return false;
		}

		$dataItem = DITime::newFromJD(
			end( $doc['_source'][$pid][$field] ),
			DITime::CM_GREGORIAN,
			DITime::PREC_YMDT
		);

		return $dataItem;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 *
	 * @return integer
	 */
	public function getAssociatedRev( $id ) {

		$params = [
			'index' => $this->connection->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'id'    => $id,
		];

		if ( !$this->connection->exists( $params ) ) {
			return 0;
		}

		$doc = $this->connection->get( $params + [ '_source_include' => [ "subject.rev_id" ] ] );

		if ( !isset( $doc['_source']['subject']['rev_id'] ) ) {
			return 0;
		}

		return $doc['_source']['subject']['rev_id'];
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

		$pid = $this->fieldMapper->getPID( \SMW\SQLStore\EntityStore\EntityIdManager::$special_ids['_MDAT'] );
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
						$time = DITime::newFromJD(
							end( $v[$pid][$field] ),
							DITime::CM_GREGORIAN,
							DITime::PREC_YMDT
						);
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
