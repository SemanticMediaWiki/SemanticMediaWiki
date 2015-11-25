<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\DIWikiPage;
use SMW\Query\Language\Description;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMWSql3SmwIds;
use SMW\SearchField;

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
	 * @since 2.2
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 * @param boolean|null $enabledEnhancedRegExMatchSearch
	 */
	public function __construct( QuerySegmentListBuilder $querySegmentListBuilder, $enabledEnhancedRegExMatchSearch = null ) {
		$this->querySegmentListBuilder = $querySegmentListBuilder;
		$this->comparatorMapper = new ComparatorMapper( $enabledEnhancedRegExMatchSearch );
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

		if ( $description->getComparator() === SMW_CMP_EQ ) {
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

			$value = $description->getDataItem()->getSortKey();
			$indexField = 'smw_sortkey';

			if ( $this->comparatorMapper->isEnabledEnhancedRegExMatchSearch( $description ) ) {
				$indexField = 'smw_searchkey';
				$value = SearchField::getSearchStringFrom( $value );
			}

			$comparator = $this->comparatorMapper->mapComparator(
				$description,
				$value
			);

			$db = $this->querySegmentListBuilder->getStore()->getConnection( 'mw.db' );

			$query->where = "{$query->alias}.$indexField$comparator" . $db->addQuotes( $value ) .
				" AND $query->alias.smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) .
				" AND $query->alias.smw_iw!=" . $db->addQuotes( SMW_SQL3_SMWDELETEIW );
		}

		return $query;
	}

}
