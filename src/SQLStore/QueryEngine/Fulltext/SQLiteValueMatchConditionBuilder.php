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
	 * @see ValueMatchConditionBuilder::canHaveMatchCondition
	 * @since 2.5
	 *
	 * @param ValueDescription $description
	 *
	 * @return boolean
	 */
	public function canHaveMatchCondition( ValueDescription $description ) {

		if ( !$this->isEnabled() ) {
			return false;
		}

		if ( $description->getProperty() !== null && $this->isExemptedProperty( $description->getProperty() ) ) {
			return false;
		}

		if ( !$this->searchTable->isValidByType( $description->getDataItem()->getDiType() ) ) {
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
	 * @see ValueMatchConditionBuilder::getWhereCondition
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

		$value = $this->textSanitizer->sanitize(
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
				$this->searchTable->getIdByProperty( $property )
			);
		}

		return $column . " MATCH " . $this->searchTable->addQuotes( $value ) . "$propertyCondition";
	}

}
