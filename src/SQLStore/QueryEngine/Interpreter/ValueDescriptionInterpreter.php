<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\DIWikiPage;
use SMW\Query\Language\Description;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMWSql3SmwIds;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ValueDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder;

	/**
	 * @var ComparatorMapper
	 */
	private $comparatorMapper;

	/**
	 * @var FulltextSearchTableFactory
	 */
	private $fulltextSearchTableFactory;

	/**
	 * @since 2.2
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 */
	public function __construct( QuerySegmentListBuilder $querySegmentListBuilder ) {
		$this->querySegmentListBuilder = $querySegmentListBuilder;
		$this->comparatorMapper = new ComparatorMapper();
		$this->fulltextSearchTableFactory = new FulltextSearchTableFactory();
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof ValueDescription;
	}

	/**
	 * Only type '_wpg' objects can appear on query level (essentially as nominal classes)
	 *
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return QuerySegment
	 */
	public function interpretDescription( Description $description ) {

		$query = new QuerySegment();

		if ( !$description->getDataItem() instanceof DIWikiPage ) {
			return $query;
		}

		$comparator = $description->getComparator();
		$value = $description->getDataItem()->getSortKey();

		// A simple value match using the `~~Foo` will initiate a fulltext
		// search without being bound to a property allowing a broad match
		// search
		if ( ( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) && strpos( $value, '~' ) !== false ) {

			$fulltextSearchSupport = $this->addFulltextSearchCondition(
				$query,
				$comparator,
				$value
			);

			if ( $fulltextSearchSupport ) {
				return $query;
			}
		}

		if ( $comparator === SMW_CMP_EQ ) {
			$query->type = QuerySegment::Q_VALUE;

			$oid = $this->querySegmentListBuilder->getStore()->getObjectIds()->getSMWPageID(
				$description->getDataItem()->getDBkey(),
				$description->getDataItem()->getNamespace(),
				$description->getDataItem()->getInterwiki(),
				$description->getDataItem()->getSubobjectName()
			);

			$query->joinfield = array( $oid );
		} else { // Join with SMW IDs table needed for other comparators (apply to title string).
			$query->joinTable = SMWSql3SmwIds::TABLE_NAME;
			$query->joinfield = "{$query->alias}.smw_id";

			$comparator = $this->comparatorMapper->mapComparator(
				$description,
				$value
			);

			$db = $this->querySegmentListBuilder->getStore()->getConnection( 'mw.db.queryengine' );

			$query->where = "{$query->alias}.smw_sortkey$comparator" . $db->addQuotes( $value );
		}

		return $query;
	}

	private function addFulltextSearchCondition( $query, $comparator, &$value ) {

		// Remove remaining ~ from the search string
		$value = str_replace( '~', '', $value );

		$valueMatchConditionBuilder = $this->fulltextSearchTableFactory->newValueMatchConditionBuilderByType(
			$this->querySegmentListBuilder->getStore()
		);

		if ( !$valueMatchConditionBuilder->isEnabled() || !$valueMatchConditionBuilder->hasMinTokenLength( $value ) ) {
			return false;
		}

		$query->joinTable = $valueMatchConditionBuilder->getTableName();
		$query->joinfield = "{$query->alias}.s_id";
		$query->components = array();

		$query->where = $valueMatchConditionBuilder->getWhereCondition(
			new ValueDescription( new DIBlob( $value ), null, $comparator ),
			$query->alias
		);

		return $query;
	}

}
