<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\ValueDescription;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ValueMatchConditionBuilder {

	protected TextSanitizer $textSanitizer;

	protected SearchTable $searchTable;

	/**
	 * @since 2.5
	 *
	 * @param TextSanitizer $textSanitizer
	 * @param SearchTable $searchTable
	 */
	public function __construct( TextSanitizer $textSanitizer, SearchTable $searchTable ) {
		$this->textSanitizer = $textSanitizer;
		$this->searchTable = $searchTable;
	}

	/**
	 * @since 2.5
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return $this->searchTable->isEnabled();
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getTableName(): string {
		return $this->searchTable->getTableName();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $value
	 *
	 * @return bool
	 */
	public function hasMinTokenLength( $value ): bool {
		return $this->searchTable->hasMinTokenLength( $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return bool
	 */
	public function isExemptedProperty( Property $property ) {
		return $this->searchTable->isExemptedProperty( $property );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $temporaryTable
	 *
	 * @return string
	 */
	public function getSortIndexField( $temporaryTable = '' ): string {
		return ( $temporaryTable !== '' ? $temporaryTable . '.' : '' ) . $this->searchTable->getSortField();
	}

	/**
	 * @since 2.5
	 *
	 * @param ValueDescription $description
	 *
	 * @return bool
	 */
	public function canHaveMatchCondition( ValueDescription $description ): bool {
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
	public function getWhereCondition( ValueDescription $description, $temporaryTable = '' ): string {
		return '';
	}

	protected function getMatchableTextFromDescription( ValueDescription $description ) {
		$matchableText = false;

		if ( $description->getDataItem() instanceof Blob ) {
			$matchableText = $description->getDataItem()->getString();
		}

		if ( $description->getDataItem() instanceof Uri || $description->getDataItem() instanceof WikiPage ) {
			$matchableText = $description->getDataItem()->getSortKey();
		}

		return $matchableText;
	}

}
