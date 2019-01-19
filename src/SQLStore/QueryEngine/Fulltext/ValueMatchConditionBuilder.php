<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\ValueDescription;
use SMWDIBlob as DIBlob;
use SMWDIUri as DIUri;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ValueMatchConditionBuilder {

	/**
	 * @var TextSanitizer
	 */
	protected $textSanitizer;

	/**
	 * @var SearchTable
	 */
	protected $searchTable;

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
		return $this->searchTable->hasMinTokenLength( $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $property
	 *
	 * @return boolean
	 */
	public function isExemptedProperty( DIProperty $property ) {
		return $this->searchTable->isExemptedProperty( $property );
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
	public function canHaveMatchCondition( ValueDescription $description ) {
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
		return '';
	}

	protected function getMatchableTextFromDescription( ValueDescription $description ) {

		$matchableText = false;

		if ( $description->getDataItem() instanceof DIBlob ) {
			$matchableText = $description->getDataItem()->getString();
		}

		if ( $description->getDataItem() instanceof DIUri || $description->getDataItem() instanceof DIWikiPage ) {
			$matchableText = $description->getDataItem()->getSortKey();
		}

		return $matchableText;
	}

}
