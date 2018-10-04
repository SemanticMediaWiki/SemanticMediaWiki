<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\Localizer;
use SMW\MediaWiki\Database;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleLookup extends Lookup {

	const VERSION = 1;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var ArticleAugmentor
	 */
	private $articleAugmentor;

	/**
	 * @since 3.0
	 *
	 * @param Database $connection
	 * @param ArticleAugmentor $articleAugmentor
	 */
	public function __construct( Database $connection, ArticleAugmentor $articleAugmentor ) {
		$this->connection = $connection;
		$this->articleAugmentor = $articleAugmentor;
	}

	/**
	 * @since 3.0
	 *
	 * @return string|integer
	 */
	public function getVersion() {
		return 'ArticleLookup:' . self::VERSION;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( array $parameters ) {

		$limit = 50;
		$offset = 0;
		$namespace = null;

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		if ( isset( $parameters['namespace'] ) ) {
			$namespace = $parameters['namespace'];
		}

		if ( isset( $parameters['search'] ) ) {
			list( $list, $continueOffset ) = $this->search( $limit, $offset, $parameters['search'], $namespace );
		}

		// Changing this output format requires to set a new version
		$res = [
			'query' => $list,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => 'article',
				'limit' => $limit,
				'count' => count( $list )
			]
		];

		$this->articleAugmentor->augment(
			$res,
			$parameters
		);

		return $res;
	}

	private function search( $limit, $offset, $search, $namespace = null ) {

		$search = $this->getSearchTerm( $search, $namespace );

		$escapeChar = '`';
		$list = [];

		$search = str_replace(
			[ ' ', $escapeChar, '%', '_' ],
			[ '_', "{$escapeChar}{$escapeChar}", "{$escapeChar}%", "{$escapeChar}_" ],
			$search
		);

		$limit = $limit + 1;
		$conditions = '';

		$fields = [
			'page_id',
			'page_namespace',
			'page_title'
		];

		$options = [
			'LIMIT' => $limit,
			'OFFSET' => $offset,
			'ORDER BY' => "page_title,page_namespace"
		];

		$conds = [
			'%' . $search . '%',
			'%' . ucfirst( $search ) . '%',
			'%' . strtoupper( $search ) . '%',
			'%' . strtolower( $search ) . '%'
		];

		foreach ( $conds as $s ) {
			$conditions .= ( $conditions !== '' ? ' OR ' : '' ) . "page_title LIKE ";
			$conditions .= $this->connection->addQuotes( $s );
			$conditions .= ' ESCAPE ' . $this->connection->addQuotes( $escapeChar );
		}

		if ( $namespace !== null ) {
			$conditions = 'page_namespace=' . $this->connection->addQuotes( $namespace ) . ' AND ('. $conditions. ')';
		}

		$res = $this->connection->select(
			[ 'page'],
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		$count = 0;
		$continueOffset = 0;

		foreach ( $res as $row ) {

			$key = $row->page_title;
			$count++;

			if ( $count > ( $limit - 1 ) ) {
				$continueOffset = $offset + $limit;
				break;
			}

			$label = str_replace( '_', ' ', $row->page_title );

			$list[$key.'#'.$row->page_namespace] = [
				 // Only keep the ID as internal field which is
				 // removed by the Augmentor
				'id'    => $row->page_id,
				'label' => $label,
				'key'   => $key,
				'ns'    => $row->page_namespace
			];
		}

		return [ $list, $continueOffset ];
	}

	private function getSearchTerm( $search, &$namespace = null ) {

		if ( strpos( $search, ':' ) !== false ) {
			list( $ns, $term ) = explode( ':', $search );

			if ( ( $namespace = Localizer::getInstance()->getNamespaceIndexByName( $ns ) ) !== false ) {
				$search = $term;
			} else {
				$namespace = null;
			}
		}

		return $search;
	}

}
