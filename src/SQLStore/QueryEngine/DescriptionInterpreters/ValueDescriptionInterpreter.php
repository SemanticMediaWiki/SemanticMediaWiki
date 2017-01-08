<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

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
		$property = $description->getProperty();
		$value = $description->getDataItem()->getSortKey();

		// A simple value match using the `~~Foo` will initiate a fulltext
		// search without being bound to a property allowing a broad match
		// search
		if ( ( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) ) {

			$fulltextSearchSupport = $this->addFulltextSearchCondition(
				$description,
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

	private function addFulltextSearchCondition( $description, $query, $comparator, &$value ) {

		// Uses ~~ wide proximity?
		$usesWidePromixity = false;

		// If a remaining ~ is present then the user searched with a ~~ string
		// where the Comparator already matched/removed the first one
		if ( substr( $value, 0, 1 ) === '~' ) {
			$value = substr( $value, 1 );
			$usesWidePromixity = true;
		}

		// If it is not a wide proximity search and it doesn't have a property then
		// don't try to match using the fulltext index (redirect [[~Foo]] to LIKE)
		if ( !$usesWidePromixity && $description->getProperty() === null ) {
			return false;
		}

		$valueMatchConditionBuilder = $this->fulltextSearchTableFactory->newValueMatchConditionBuilderByType(
			$this->querySegmentListBuilder->getStore()
		);

		if ( !$valueMatchConditionBuilder->isEnabled() || !$valueMatchConditionBuilder->hasMinTokenLength( $value ) ) {
			return false;
		}

		if ( !$usesWidePromixity && !$valueMatchConditionBuilder->canApplyFulltextSearchMatchCondition( $description ) ) {
			return false;
		}

		$query->joinTable = $valueMatchConditionBuilder->getTableName();
		$query->joinfield = "{$query->alias}.s_id";
		$query->indexField = 's_id';
		$query->components = array();

		$query->where = $valueMatchConditionBuilder->getWhereCondition(
			new ValueDescription( new DIBlob( $value ), null, $comparator ),
			$query->alias
		);

		return $query;
	}

}
