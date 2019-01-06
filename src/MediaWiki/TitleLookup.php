<?php

namespace SMW\MediaWiki;

use RuntimeException;
use Title;

/**
 * A convenience class to encapsulate MW related database interaction
 *
 * @note This is an internal class and should not be used outside of smw-core
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class TitleLookup {

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var integer
	 */
	private $namespace = null;

	/**
	 * @since 1.9.2
	 *
	 * @param Database $connection
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $namespace
	 *
	 * @return TitleLookup
	 */
	public function setNamespace( $namespace ) {
		$this->namespace = $namespace;
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return Title[]
	 * @throws RuntimeException
	 */
	public function selectAll() {

		if ( $this->namespace === null ) {
			throw new RuntimeException( 'Unrestricted selection without a namespace is not supported' );
		}

		if ( $this->namespace === NS_CATEGORY ) {
			$tableName = 'category';
			$fields = [ 'cat_title' ];
			$conditions = '';
			$options = [ 'USE INDEX' => 'cat_title' ];
		} else {
			$tableName = 'page';
			$fields = [ 'page_namespace', 'page_title' ];
			$conditions = [ 'page_namespace' => $this->namespace ];
			$options = [ 'USE INDEX' => 'PRIMARY' ];
		}

		$res = $this->connection->select(
			$tableName,
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		return $this->makeTitlesFromSelection( $res );
	}

	/**
	 * @since 2.4
	 *
	 * @return Title[]
	 */
	public function getRedirectPages() {

		$conditions = [];
		$options = [];

		$res = $this->connection->select(
			[ 'page', 'redirect' ],
			[ 'page_namespace', 'page_title' ],
			$conditions,
			__METHOD__,
			$options,
			[ 'page' => [ 'INNER JOIN', [ 'page_id=rd_from' ] ] ]
		);

		return $this->makeTitlesFromSelection( $res );
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $startId
	 * @param int $endId
	 *
	 * @return Title[]
	 * @throws RuntimeException
	 */
	public function selectByIdRange( $startId = 0, $endId = 0 ) {

		if ( $this->namespace === null ) {
			throw new RuntimeException( 'Unrestricted selection without a namespace is not supported' );
		}

		if ( $this->namespace === NS_CATEGORY ) {
			$tableName = 'category';
			$fields = [ 'cat_title', 'cat_id' ];
			$conditions = [ "cat_id BETWEEN $startId AND $endId" ];
			$options = [ 'ORDER BY' => 'cat_id ASC', 'USE INDEX' => 'cat_title' ];
		} else {
			$tableName = 'page';
			$fields = [ 'page_namespace', 'page_title', 'page_id' ];
			$conditions = [ "page_id BETWEEN $startId AND $endId" ] + [ 'page_namespace' => $this->namespace ];
			$options = [ 'ORDER BY' => 'page_id ASC', 'USE INDEX' => 'PRIMARY' ];
		}

		$res = $this->connection->select(
			$tableName,
			$fields,
			$conditions,
			__METHOD__,
			$options
		);

		return $this->makeTitlesFromSelection( $res );
	}

	/**
	 * @since 1.9.2
	 *
	 * @return int
	 */
	public function getMaxId() {

		if ( $this->namespace === NS_CATEGORY ) {
			$tableName = 'category';
			$var = 'MAX(cat_id)';
		} else {
			$tableName = 'page';
			$var = 'MAX(page_id)';
		}

		return (int)$this->connection->selectField(
			$tableName,
			$var,
			false,
			__METHOD__
		);
	}

	protected function makeTitlesFromSelection( $res ) {

		$pages = [];

		if ( $res === false ) {
			return $pages;
		}

		foreach ( $res as $row ) {
			$pages[] = $this->newTitleFromRow( $row );
		}

		return $pages;
	}

	private function newTitleFromRow( $row ) {

		if ( $this->namespace === NS_CATEGORY ) {
			$ns = NS_CATEGORY;
			$title = $row->cat_title;
		} elseif ( isset( $row->rd_namespace ) ) {
			$ns =  $row->rd_namespace;
			$title = $row->rd_title;
		} else {
			$ns =  $row->page_namespace;
			$title = $row->page_title;
		}

		return Title::makeTitle( $ns, $title );
	}

}
