<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\Query\Language\ValueDescription;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SQLiteValueMatchConditionBuilder extends ValueMatchConditionBuilder {

	/**
	 * @var SearchTable
	 */
	private $searchTable;

	/**
	 * @since 2.5
	 *
	 * @param SearchTable $searchTable
	 */
	public function __construct( SearchTable $searchTable ) {
		$this->searchTable = $searchTable;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->searchTable->isEnabled();
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->searchTable->getTableName();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public function hasMinTokenLength( $value ) {
		return mb_strlen( $value ) >= $this->searchTable->getMinTokenSize();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $temporaryTable
	 *
	 * @return string
	 */
	public function getSortIndexField( $temporaryTable = '' ) {
		return ( $temporaryTable !== '' ? $temporaryTable . '.' : '' ) . $this->searchTable->getSortField();
	}

	/**
	 * @since 2.5
	 *
	 * @param ValueDescription $description
	 *
	 * @return boolean
	 */
	public function canApplyFulltextSearchMatchCondition( ValueDescription $description ) {

		if ( !$this->isEnabled() || $description->getProperty() === null ) {
			return false;
		}

		if ( $this->searchTable->isExemptedProperty( $description->getProperty() ) ) {
			return false;
		}

		$matchableText = $this->getMatchableTextFromDescription(
			$description
		);

		$comparator = $description->getComparator();

		if ( $matchableText && ( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) ) {
			return $this->hasMinTokenLength( str_replace( '*', '', $matchableText ) );
		}

		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @param ValueDescription $description
	 * @param string $temporaryTable
	 *
	 * @return string
	 */
	public function getWhereCondition( ValueDescription $description, $temporaryTable = '' ) {

		$matchableText = $this->getMatchableTextFromDescription(
			$description
		);

		$value = $this->searchTable->getTextSanitizer()->sanitize(
			$matchableText,
			true
		);

		// A leading or trailing minus sign indicates that this word must not
		// be present in any of the rows that are returned.
		// InnoDB only supports leading minus signs.
		if ( $description->getComparator() === SMW_CMP_NLKE ) {
			$value = '-' . $value;
		}

		// Something like [[Has text::!~database]] will cause a
		// "malformed MATCH expression" due to "An FTS query may not consist
		// entirely of terms or term-prefix queries with unary "-" operators
		// attached to them." and doing "NOT database" will result in an empty
		// result set

		$temporaryTable = $temporaryTable !== '' ? $temporaryTable . '.' : '';
		$column = $temporaryTable . $this->searchTable->getIndexField();

		$property = $description->getProperty();
		$propertyCondition = '';

		// Full text is collected in a single table therefore limit the match
		// process by adding the PID as an additional condition
		if ( $property !== null ) {
			$propertyCondition = ' AND ' . $temporaryTable . 'p_id=' . $this->searchTable->addQuotes(
				$this->searchTable->getPropertyID( $property )
			);
		}

		return $column . " MATCH " . $this->searchTable->addQuotes( $value ) . "$propertyCondition";
	}

}
