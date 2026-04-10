<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DataItems\Blob;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\Description;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ValueDescriptionInterpreter implements DescriptionInterpreter {

	private ComparatorMapper $comparatorMapper;

	private FulltextSearchTableFactory $fulltextSearchTableFactory;

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly ConditionBuilder $conditionBuilder,
	) {
		$this->comparatorMapper = new ComparatorMapper();
		$this->fulltextSearchTableFactory = new FulltextSearchTableFactory();
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function canInterpretDescription( Description $description ): bool {
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
	public function interpretDescription( Description $description ): QuerySegment {
		$query = new QuerySegment();

		if ( !$description instanceof ValueDescription || !$description->getDataItem() instanceof WikiPage ) {
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
			$dataItem = $description->getDataItem();

			$oid = $this->store->getObjectIds()->getSMWPageID(
				$dataItem->getDBkey(),
				$dataItem->getNamespace(),
				$dataItem->getInterwiki(),
				$dataItem->getSubobjectName()
			);

			$query->joinfield = [ $oid ];
		} else { // Join with SMW IDs table needed for other comparators (apply to title string).
			$query->joinTable = SQLStore::ID_TABLE;
			$query->joinfield = "{$query->alias}.smw_id";

			$comparator = $this->comparatorMapper->mapComparator(
				$description,
				$value
			);

			$connection = $this->store->getConnection( 'mw.db.queryengine' );

			$query->where = "{$query->alias}.smw_sortkey$comparator" . $connection->addQuotes( $value );
		}

		return $query;
	}

	private function addFulltextSearchCondition( ValueDescription $description, QuerySegment $query, $comparator, &$value ): false|QuerySegment {
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
			$this->store
		);

		if ( !$valueMatchConditionBuilder->isEnabled() || !$valueMatchConditionBuilder->hasMinTokenLength( $value ) ) {
			return false;
		}

		if ( !$usesWidePromixity && !$valueMatchConditionBuilder->canHaveMatchCondition( $description ) ) {
			return false;
		}

		$query->joinTable = $valueMatchConditionBuilder->getTableName();
		$query->joinfield = "{$query->alias}.s_id";
		$query->indexField = 's_id';
		$query->components = [];

		$query->where = $valueMatchConditionBuilder->getWhereCondition(
			new ValueDescription( new Blob( $value ), null, $comparator ),
			$query->alias
		);

		return $query;
	}

}
