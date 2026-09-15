<?php

namespace SMW\SQLStore\EntityStore\DataItemHandlers;

use SMW\DataItems\DataItem;
use SMW\DataItems\Uri;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\TableBuilder\FieldType;

/**
 * This class implements Store access to Uri data items.
 *
 * @license GPL-2.0-or-later
 * @since 1.8
 *
 * @author Nischay Nahata
 */
class DIUriHandler extends DataItemHandler {

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableFields(): array {
		return [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => $this->getCharFieldType()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getFetchFields(): array {
		return [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => $this->getCharFieldType()
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getTableIndexes(): array {
		return [
			'p_id,o_serialized',
		];
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getIndexHint( $key ): string {
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids`
		// INNER JOIN `smw_di_uri` AS t1
		// FORCE INDEX(s_id) ON t1.s_id=smw_id
		// WHERE t1.p_id='310165' AND smw_iw!=':smw' AND smw_iw!=':smw-delete' AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id LIMIT 26
		//
		// 606.8370ms SMWSQLStore3Readers::getPropertySubjects
		//
		// vs.
		//
		// SELECT smw_id, smw_title, smw_namespace, smw_iw, smw_subobject, smw_sortkey, smw_sort
		// FROM `smw_object_ids`
		// INNER JOIN `smw_di_uri` AS t1 ON t1.s_id=smw_id
		// WHERE t1.p_id='310165' AND smw_iw!=':smw' AND smw_iw!=':smw-delete' AND smw_iw!=':smw-redi'
		// GROUP BY smw_sort, smw_id LIMIT 26
		//
		// 8052.2099ms SMWSQLStore3Readers::getPropertySubjects
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
	public function getWhereConds( DataItem $dataItem ): array {
		$serialization = $dataItem->getSerialization();
		return [ 'o_serialized' => substr( $serialization, 0, $this->getMaxLength() ) ];
	}

	/**
	 * Values written before 7.3.0 went through rawurldecode() on the way in, so
	 * a URI carrying percent-encoded octets can sit on disk in a different form
	 * than getWhereConds() now produces.
	 *
	 * The old form is only returned when it contains a character that URIValue
	 * stores percent-encoded whether it was typed raw or encoded. A row holding
	 * such a character raw, written by an earlier version or by code that creates
	 * the Uri directly, is then the same value spelled differently. It is not
	 * returned when, as with `%2F` decoding to `/`, it could be a different value.
	 *
	 * @since 7.3.0
	 */
	public function getLegacyWhereConds( DataItem $dataItem ): array {
		$serialization = $dataItem->getSerialization();
		$maxLength = $this->getMaxLength();

		$current = substr( $serialization, 0, $maxLength );
		$legacy = substr( rawurldecode( $serialization ), 0, $maxLength );

		if ( $legacy !== $current && $this->isQueryable( $legacy ) && $this->containsAlwaysEncodedCharacter( $legacy ) ) {
			return [ 'o_serialized' => $legacy ];
		}

		return [];
	}

	/**
	 * A NUL byte or invalid UTF-8 in a string literal breaks the query on some
	 * database backends.
	 */
	private function isQueryable( string $value ): bool {
		return !str_contains( $value, "\0" ) && mb_check_encoding( $value, 'UTF-8' );
	}

	/**
	 * URIValue stores these characters percent-encoded whether they were typed
	 * raw or encoded.
	 */
	private function containsAlwaysEncodedCharacter( string $legacy ): bool {
		return preg_match( '/[\x01-\x20"\'<>\[\\\\\]^`{|}\x7F]/', $legacy ) === 1;
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getInsertValues( DataItem $dataItem ): array {
		$serialization = $dataItem->getSerialization();
		$text = mb_strlen( $serialization ) <= $this->getMaxLength() ? null : $serialization;

		// bytea type handling
		if ( $text !== null && $this->isDbType( 'postgres' ) ) {
			$connection = $this->store->getConnection( 'mw.db' );
			$text = $connection->escape_bytea( $text );
		}

		return [
			'o_blob' => $text,
			'o_serialized' => substr( $serialization, 0, $this->getMaxLength() ),
		];
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getIndexField(): string {
		return 'o_serialized';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function getLabelField(): string {
		return 'o_serialized';
	}

	/**
	 * @since 1.8
	 *
	 * {@inheritDoc}
	 */
	public function dataItemFromDBKeys( $dbkeys ): Uri {
		if ( !is_array( $dbkeys ) || count( $dbkeys ) != 2 ) {
			throw new DataItemHandlerException( 'Failed to create data item from DB keys.' );
		}

		if ( $this->isDbType( 'postgres' ) ) {
			$connection = $this->store->getConnection( 'mw.db' );
			$dbkeys[0] = $connection->unescape_bytea( $dbkeys[0] ?? '' );
		}

		return Uri::doUnserialize( $dbkeys[0] == '' ? $dbkeys[1] : $dbkeys[0] );
	}

	private function getMaxLength(): int {
		$length = 255;

		if ( $this->hasFeature( SMW_FIELDT_CHAR_LONG ) ) {
			$length = FieldType::CHAR_LONG_LENGTH;
		}

		return $length;
	}

	private function getCharFieldType(): string {
		$fieldType = FieldType::FIELD_TITLE;

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
