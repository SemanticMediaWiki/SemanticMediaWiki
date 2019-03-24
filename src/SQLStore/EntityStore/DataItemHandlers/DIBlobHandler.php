<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;

/**
 * This class implements Store access to blob (string) data items.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DIBlobHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields() {
		return [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_hash' => $this->getCharFieldType()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields() {
		return [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_hash' => $this->getCharFieldType()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes() {
		return [

			's_id,o_hash',

			// pvalue select
			// SELECT p_id,o_hash FROM `smw_di_blob` WHERE p_id = '310174' AND ( o_hash LIKE '%test%' ) LIMIT 11
			'p_id,o_hash',
		];
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getIndexHint( $key ) {

		// Store::getPropertySubjects has seen to choose the wrong index

		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids`
		// INNER JOIN `smw_di_blob` AS t1 FORCE INDEX(s_id) ON t1.s_id=smw_id
		// WHERE t1.p_id='310174' AND smw_iw!=':smw'
		// AND smw_iw!=':smw-delete' AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id LIMIT 26
		//
		// 137.4161ms SMWSQLStore3Readers::getPropertySubjects
		//
		// vs.
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids`
		// INNER JOIN `smw_di_blob` AS t1 ON t1.s_id=smw_id
		// WHERE t1.p_id='310174' AND smw_iw!=':smw' AND smw_iw!=':smw-delete'
		// AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id LIMIT 26
		//
		// 23482.1451ms SMWSQLStore3Readers::getPropertySubjects
		if ( $key === self::IHINT_PSUBJECTS && $this->isDbType( 'mysql' ) ) {
			return 's_id';
		}

		return '';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getWhereConds( DataItem $dataItem ) {

		$isKeyword = $dataItem->getOption( 'is.keyword' );
		$text = $dataItem->getString();

		return [
			'o_hash' => $isKeyword ? $dataItem->normalize( $text ) : $this->makeHash( $text )
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ) {

		$isKeyword = $dataItem->getOption( 'is.keyword' );

		$text = htmlspecialchars_decode( trim( $dataItem->getString() ), ENT_QUOTES );
		$hash = $isKeyword ? $dataItem->normalize( $text ) : $this->makeHash( $text );

		if ( $this->isDbType( 'postgres' ) ) {
			$text = pg_escape_bytea( $text );
		}

		if ( mb_strlen( $text ) <= $this->getMaxLength() && !$isKeyword ) {
			$text = null;
		}

		return [
			'o_blob' => $text,
			'o_hash' => $hash,
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField() {
		return 'o_hash';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField() {
		return 'o_hash';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ) {

		if ( !is_array( $dbkeys ) || count( $dbkeys ) != 2 ) {
			throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
		}

		if ( $this->isDbType( 'postgres' ) ) {
			$dbkeys[0] = pg_unescape_bytea( $dbkeys[0] );
		}

		// empty blob: use "hash" string
		if ( $dbkeys[0] == '' ) {
			return new DIBlob( $dbkeys[1] );
		}

		return new DIBlob( $dbkeys[0] );
	}

	/**
	* Method to make a hashed representation for strings of length greater
	* than DIBlobHandler::getMaxLength to be used for selecting and sorting.
	*
	* @since 1.8
	* @param $string string
	*
	* @return string
	*/
	private function makeHash( $string ) {

		$length = $this->getMaxLength();

		if( mb_strlen( $string ) <= $length ) {
			return $string;
		}

		return mb_substr( $string, 0, $length - 32 ) . md5( $string );
	}

	/**
	 * Maximal number of bytes (chars) to be stored in the hash field of
	 * the table. Must not be bigger than 255 (the length of our VARCHAR
	 * field in the DB). Strings that are longer than this will be stored
	 * as a blob, and the hash will only start with the original string
	 * but the last 32 bytes are used for a hash. So the minimal portion
	 * of the string that is stored literally in the hash is 32 chars
	 * less.
	 *
	 * The value of 72 was chosen since it leads to a smaller index size
	 * at the cost of needing more blobs in cases where many strings are
	 * of length 73 to 255. But keeping the index small seems more
	 * important than saving disk space. Also, with 72 bytes there are at
	 * least 40 bytes of content available for sorting and prefix matching,
	 * which should be more than enough in most contexts.
	 *
	 * @since 1.8
	 *
	 * Using `SMW_FIELDT_CHAR_LONG` as option in `smwgFieldTypeFeatures`
	 * will extend the field size to 300 and expands the maximum matchable
	 * string length to 300-32 for LIKE/NLIKE queries.
	 *
	 * @since 3.0
	 */
	private function getMaxLength() {

		$length = 72;

		if ( $this->hasFeature( SMW_FIELDT_CHAR_LONG ) ) {
			$length = FieldType::CHAR_LONG_LENGTH;
		}

		return $length;
	}

	private function getCharFieldType() {

		// http://sqlite.1065341.n5.nabble.com/Leading-zeros-disappear-td60515.html
		// @Test:[p-0430]
		if ( $this->isDbType( 'sqlite' ) ) {
			$fieldType = FieldType::TYPE_TEXT;
		} else {
			$fieldType = FieldType::FIELD_TITLE;
		}

		if ( $this->hasFeature( SMW_FIELDT_CHAR_NOCASE ) ) {
			$fieldType = FieldType::TYPE_CHAR_NOCASE;
		}

		if ( $this->hasFeature( SMW_FIELDT_CHAR_LONG ) ) {
			$fieldType = FieldType::TYPE_CHAR_LONG;
		}

		if ( $this->hasFeature( SMW_FIELDT_CHAR_LONG ) && $this->hasFeature( SMW_FIELDT_CHAR_NOCASE ) ) {
			$fieldType = FieldType::TYPE_CHAR_LONG_NOCASE;
		}

		return $fieldType;
	}

}
