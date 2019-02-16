<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\MediaWiki\Connection\Sequence;
use SMW\MediaWiki\JobFactory;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IdChanger {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var JobFactory
	 */
	private $jobFactory;

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 * @param JobFactory|null $jobFactory
	 */
	public function __construct( SQLStore $store, JobFactory $jobFactory = null ) {
		$this->store = $store;
		$this->jobFactory = $jobFactory;

		if ( $this->jobFactory === null ) {
			$this->jobFactory = new JobFactory();
		}
	}

	/**
	 * Change an internal id to another value. If no target value is given, the
	 * value is changed to become the last id entry (based on the automatic id
	 * increment of the database). Whatever currently occupies this id will be
	 * moved consistently in all relevant tables. Whatever currently occupies
	 * the target id will be ignored (it should be ensured that nothing is
	 * moved to an id that is still in use somewhere).
	 *
	 * @since 3.1
	 *
	 * @param integer $curid
	 * @param integer $targetid
	 *
	 * @return \stdClass
	 */
	public function move( $curid, $targetid = 0 ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			'*',
			[
				'smw_id' => $curid
			],
			__METHOD__
		);

		// No id at current position, ignore
		if ( $row === false ) {
			return;
		}

		$connection->beginAtomicTransaction( __METHOD__ );

		// Bug 42659
		$sequence = Sequence::makeSequence( SQLStore::ID_TABLE, 'smw_id' );
		$id = $targetid == 0 ? $connection->nextSequenceValue( $sequence ) : $targetid;

		$hash = [
			$row->smw_title,
			(int)$row->smw_title,
			$row->smw_iw,
			$row->smw_subobject
		];

		$connection->insert(
			SQLStore::ID_TABLE,
			[
				'smw_id' => $id,
				'smw_title' => $row->smw_title,
				'smw_namespace' => $row->smw_namespace,
				'smw_iw' => $row->smw_iw,
				'smw_subobject' => $row->smw_subobject,
				'smw_sortkey' => $row->smw_sortkey,
				'smw_sort' => $row->smw_sort,
				'smw_hash' => IdCacheManager::computeSha1( $hash )
			],
			__METHOD__
		);

		$targetid = $targetid == 0 ? $connection->insertId() : $targetid;

		$connection->delete(
			SQLStore::ID_TABLE,
			[
				'smw_id' => $curid
			],
			__METHOD__
		);

		$row->smw_id = $targetid;

		$this->change(
			$curid,
			$targetid,
			$row->smw_namespace,
			$row->smw_namespace
		);

		$connection->endAtomicTransaction( __METHOD__ );

		if ( ( $title = \Title::newFromText( $row->smw_title, $row->smw_namespace ) ) !== null ) {
			$updateJob = $this->jobFactory->newUpdateJob( $title, [ 'origin' => __METHOD__ ] );
			$updateJob->insert();
		}

		return $row;
	}

	/**
	 * Change an SMW page id across all relevant tables. The redirect table
	 * is also updated (without much effect if the change happended due to
	 * some redirect, since the table should not contain the id of the
	 * redirected page). If namespaces are given, then they are used to
	 * delete any entries that are limited to one particular namespace (e.g.
	 * only properties can be used as properties) instead of moving them.
	 *
	 * The id in the SMW IDs table is not touched.
	 *
	 * @note This method only changes internal page IDs in SMW. It does not
	 * assume any change in (title-related) data, as e.g. in a page move.
	 * Internal objects (subobject) do not need to be updated since they
	 * refer to the title of their parent page, not to its ID.
	 *
	 * @since 1.8
	 *
	 * @param integer $old_id numeric ID that is to be changed
	 * @param integer $new_id numeric ID to which the records are to be changed
	 * @param integer $old_ns namespace of old id's page (-1 to ignore it)
	 * @param integer $new_ns namespace of new id's page (-1 to ignore it)
	 * @param boolean $s_data stating whether to update subject references
	 * @param boolean $po_data stating if to update property/object references
	 */
	public function change( $old_id, $new_id, $old_ns = -1, $new_ns = -1, $s_data = true, $po_data = true ) {

		$connection = $this->store->getConnection( 'mw.db' );

		// Change all id entries in property tables:
		foreach ( $this->store->getPropertyTables() as $proptable ) {

			if ( $s_data && $proptable->usesIdSubject() ) {

				$row = $connection->selectRow(
					$proptable->getName(),
					[ 's_id' ],
					[ 's_id' => $old_id ],
					__METHOD__
				);

				if ( $row === false ) {
					continue;
				}

				$connection->update(
					$proptable->getName(),
					[ 's_id' => $new_id ],
					[ 's_id' => $old_id ],
					__METHOD__
				);
			}

			if ( $po_data ) {
				if ( ( ( $old_ns == -1 ) || ( $old_ns == SMW_NS_PROPERTY ) ) && ( !$proptable->isFixedPropertyTable() ) ) {
					if ( ( $new_ns == -1 ) || ( $new_ns == SMW_NS_PROPERTY ) ) {
						$connection->update(
							$proptable->getName(),
							[ 'p_id' => $new_id ],
							[ 'p_id' => $old_id ],
							__METHOD__
						);
					} else {
						$connection->delete(
							$proptable->getName(),
							[ 'p_id' => $old_id ],
							__METHOD__
						);
					}
				}

				foreach ( $proptable->getFields( $this->store ) as $fieldName => $fieldType ) {

					if ( $fieldType !== FieldType::FIELD_ID ) {
						continue;
					}

					$row = $connection->selectRow(
						$proptable->getName(),
						[ $fieldName ],
						[ $fieldName => $old_id ],
						__METHOD__
					);

					if ( $row === false ) {
						continue;
					}

					$connection->update(
						$proptable->getName(),
						[ $fieldName => $new_id ],
						[ $fieldName => $old_id ],
						__METHOD__
					);
				}
			}
		}

		$this->update_concept( $old_id, $new_id, $old_ns, $new_ns, $s_data, $po_data );
	}

	private function update_concept( $old_id, $new_id, $old_ns, $new_ns, $s_data, $po_data ) {

		$connection = $this->store->getConnection( 'mw.db' );

		if ( $s_data && ( ( $old_ns == -1 ) || ( $old_ns == SMW_NS_CONCEPT ) ) ) {
			if ( ( $new_ns == -1 ) || ( $new_ns == SMW_NS_CONCEPT ) ) {
				$connection->update(
					SQLStore::CONCEPT_TABLE,
					[ 's_id' => $new_id ],
					[ 's_id' => $old_id ],
					__METHOD__
				);

				$connection->update(
					SQLStore::CONCEPT_CACHE_TABLE,
					[ 's_id' => $new_id ],
					[ 's_id' => $old_id ],
					__METHOD__
				);
			} else {
				$connection->delete(
					SQLStore::CONCEPT_TABLE,
					[ 's_id' => $old_id ],
					__METHOD__
				);

				$connection->delete(
					SQLStore::CONCEPT_CACHE_TABLE,
					[ 's_id' => $old_id ],
					__METHOD__
				);
			}
		}

		if ( $po_data ) {
			$connection->update(
				SQLStore::CONCEPT_CACHE_TABLE,
				[ 'o_id' => $new_id ],
				[ 'o_id' => $old_id ],
				__METHOD__
			);
		}
	}

}
