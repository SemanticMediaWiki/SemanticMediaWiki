<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

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
	 * @since 2.5
	 *
	 * @param TextSanitizer $textSanitizer
	 */
	public function __construct( TextSanitizer $textSanitizer ) {
		$this->textSanitizer = $textSanitizer;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getTableName() {
		return '';
	}

	/**
	 * @since 2.5
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public function hasMinTokenLength( $value ) {
		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $temporaryTable
	 *
	 * @return string
	 */
	public function getSortIndexField( $temporaryTable = '' ) {
		return '';
	}

	/**
	 * @since 2.5
	 *
	 * @param ValueDescription $description
	 *
	 * @return boolean
	 */
	public function canApplyFulltextSearchMatchCondition( ValueDescription $description ) {
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

	protected function getMatchableTextFromDescription( $description ) {

		$matchableText = false;

		if ( $description->getDataItem() instanceof DIBlob ) {
			$matchableText = $description->getDataItem()->getString();
		}

		if ( $description->getDataItem() instanceof DIUri ) {
			$matchableText = $description->getDataItem()->getSortKey();
		}

		return $matchableText;
	}

}
