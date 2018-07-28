<?php

namespace SMW\SQLStore\EntityStore;

use RuntimeException;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;

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
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
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
					if ( $fieldType === FieldType::FIELD_ID ) {
						$connection->update(
							$proptable->getName(),
							[ $fieldName => $new_id ],
							[ $fieldName => $old_id ],
							__METHOD__
						);
					}
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
