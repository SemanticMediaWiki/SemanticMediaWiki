<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\HashBuilder;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Database;
use SMW\IteratorFactory;
use SMW\RequestOptions;
use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class IdMatchFinder {

	/**
	 * @var Database|null
	 */
	private $connection = null;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var Cache
	 */
	private $inMemoryCache;

	/**
	 * @since 2.1
	 *
	 * @param Database $connection
	 * @param IteratorFactory $iteratorFactory
	 * @param Cache $inMemoryCache
	 */
	public function __construct( Database $connection, IteratorFactory $iteratorFactory, Cache $inMemoryCache ) {
		$this->connection = $connection;
		$this->iteratorFactory = $iteratorFactory;
		$this->inMemoryCache = $inMemoryCache;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $idList
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return DIWikiPage[]
	 */
	public function getDataItemsFromList( array $idList, RequestOptions $requestOptions = null ) {

		$conditions = array(
			'smw_id' => $idList,
		);

		if ( $requestOptions !== null ) {
			foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
				$conditions[] = $extraCondition;
			}
		}

		$rows = $this->connection->select(
			\SMWSQLStore3::ID_TABLE,
			array(
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			),
			$conditions,
			__METHOD__
		);

		$resultIterator = $this->iteratorFactory->newResultIterator( $rows );

		$mappingIterator = $this->iteratorFactory->newMappingIterator( $resultIterator, function( $row ) {
			return HashBuilder::createHashIdFromSegments(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject
			);
		} );

		return $mappingIterator;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 *
	 * @return DIWikiPage|null
	 */
	public function getDataItemById( $id ) {

		if ( !$this->inMemoryCache->contains( $id ) && !$this->canMatchById( $id ) ) {
			return null;
		}

		$wikiPage = HashBuilder::newDiWikiPageFromHash(
			$this->inMemoryCache->fetch( $id )
		);

		$wikiPage->setId( $id );

		return $wikiPage;
	}

	private function canMatchById( $id ) {

		$row = $this->connection->selectRow(
			\SMWSQLStore3::ID_TABLE,
			array(
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			),
			array( 'smw_id' => $id ),
			__METHOD__
		);

		if ( $row === false ) {
			return false;
		}

		$hash = HashBuilder::createHashIdFromSegments(
			$row->smw_title,
			$row->smw_namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		$this->inMemoryCache->save( $id, $hash );

		return true;
	}

}
