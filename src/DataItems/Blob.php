<?php

namespace SMW\DataItems;

/**
 * This class implements blob (long string) data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup DataItems
 */
class Blob extends DataItem {

	/**
	 * Internal value.
	 * @var string
	 */
	protected $m_string;

	public function __construct( $string ) {
		$this->m_string = trim( $string ?? '' );
	}

	public function getDIType() {
		return DataItem::TYPE_BLOB;
	}

	public function getString() {
		return $this->m_string;
	}

	public static function normalize( $text ) {
		$text = mb_strtolower( $text );

		$transliterator = Transliterator::create( 'Any-Latin; Latin-ASCII' );

		if ( $transliterator !== null ) {
			$result = $transliterator->transliterate( $text );

			if ( $result !== false ) {
				$text = $result;
			}
		}

		return mb_convert_kana( $text, 'a' );
	}

	public function getSortKey() {
		return $this->m_string;
	}

	/**
	 * @see DataItem::getSortKeyDataItem()
	 * @return DataItem
	 */
	public function getSortKeyDataItem() {
		return $this;
	}

	public function getSerialization() {
		return $this->m_string;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return Blob
	 */
	public static function doUnserialize( $serialization ) {
		return new Blob( $serialization );
	}

	public function equals( DataItem $di ) {
		if ( !( $di instanceof Blob ) ) {
			return false;
		}

		return $di->getString() === $this->m_string;
	}
}

/**
 * @deprecated since 7.0.0
 */
class_alias( Blob::class, 'SMWDIBlob' );
