<?php

namespace SMW\Elastic\Connection;

/**
 * @private
 *
 * !! Only used during integration testing!!
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TestClient extends Client {

	/**
	 * @see Client::bulk
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function bulk( array $params ) {

		if ( $params === [] ) {
			return;
		}

		$params = $params + [ 'refresh' => true ];

		return parent::bulk( $params );
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-count.html
	 * @see Client::count
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function count( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		// https://discuss.elastic.co/t/es-5-2-refresh-interval-doesnt-work-if-set-to-0/79248/2
		// Make sure the replication/index lag doesn't hinder the search
		$this->indices()->refresh( [ 'index' => $params['index'] ] );

		return parent::count( $params );
	}

	/**
	 * @see Client::search
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function search( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		$this->indices()->refresh( [ 'index' => $params['index'] ] );

		return parent::search( $params );
	}

	/**
	 * @see Client::explain
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function explain( array $params ) {

		if ( $params === [] ) {
			return [];
		}

		// https://discuss.elastic.co/t/es-5-2-refresh-interval-doesnt-work-if-set-to-0/79248/2
		// Make sure the replication/index lag doesn't hinder the search
		$this->indices()->refresh( [ 'index' => $params['index'] ] );

		return parent::explain( $params );
	}

}
