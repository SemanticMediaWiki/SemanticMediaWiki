<?php

namespace SMW\MediaWiki;

use MediaWiki\Title\Title;
use RuntimeException;
use SMW\MediaWiki\Connection\Database;

/**
 * A convenience class to encapsulate MW related database interaction
 *
 * @note This is an internal class and should not be used outside of smw-core
 *
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class TitleLookup {

	/**
	 * @var int
	 */
	private $namespace = null;

	/**
	 * @since 1.9.2
	 */
	public function __construct( private readonly Database $connection ) {
	}

	/**
	 * @since 1.9.2
	 *
	 * @param int $namespace
	 *
	 * @return TitleLookup
	 */
	public function setNamespace( $namespace ): static {
		$this->namespace = $namespace;
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return Title[]
	 * @throws RuntimeException
	 */
	public function selectAll(): array {
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

		$qb = $this->connection->newSelectQueryBuilder()
			->select( $fields )
			->from( $tableName )
			->options( $options )
			->caller( __METHOD__ );

		// $conditions is '' on the category branch (no WHERE clause); only
		// invoke where() when there is something to add. where([]) is a no-op
		// so the array branch does not need a guard.
		if ( $conditions !== '' ) {
			$qb->where( $conditions );
		}

		$res = $qb->fetchResultSet();

		return $this->makeTitlesFromSelection( $res );
	}

	/**
	 * @since 2.4
	 *
	 * @return Title[]
	 */
	public function getRedirectPages(): array {
		$res = $this->connection->newSelectQueryBuilder()
			->select( [ 'page_namespace', 'page_title' ] )
			->rawTables( [ 'page', 'redirect' ] )
			->joinConds( [ 'page' => [ 'INNER JOIN', [ 'page_id=rd_from' ] ] ] )
			->caller( __METHOD__ )
			->fetchResultSet();

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
	public function selectByIdRange( $startId = 0, $endId = 0 ): array {
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

		$res = $this->connection->newSelectQueryBuilder()
			->select( $fields )
			->from( $tableName )
			->where( $conditions )
			->options( $options )
			->caller( __METHOD__ )
			->fetchResultSet();

		return $this->makeTitlesFromSelection( $res );
	}

	/**
	 * @since 1.9.2
	 *
	 * @return int
	 */
	public function getMaxId(): int {
		if ( $this->namespace === NS_CATEGORY ) {
			$tableName = 'category';
			$var = 'MAX(cat_id)';
		} else {
			$tableName = 'page';
			$var = 'MAX(page_id)';
		}

		return (int)$this->connection->newSelectQueryBuilder()
			->select( [ $var ] )
			->from( $tableName )
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * @return mixed[]
	 */
	protected function makeTitlesFromSelection( $res ): array {
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
			$ns = $row->rd_namespace;
			$title = $row->rd_title;
		} else {
			$ns = $row->page_namespace;
			$title = $row->page_title;
		}

		return Title::makeTitle( $ns, $title );
	}

}
