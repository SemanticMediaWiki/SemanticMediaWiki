<?php

/**
 * @ingroup SMWDataItems
 */

/**
 * This class implements blob (long string) data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIBlob extends SMWDataItem {

	/**
	 * Internal value.
	 * @var string
	 */
	protected $m_string;

	public function __construct( $string ) {
		$this->m_string = trim( $string ?? '' );
	}

	public function getDIType() {
		return SMWDataItem::TYPE_BLOB;
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
	 * @see SMWDataItem::getSortKeyDataItem()
	 * @return SMWDataItem
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
	 * @return SMWDIBlob
	 */
	public static function doUnserialize( $serialization ) {
		return new SMWDIBlob( $serialization );
	}

	public function equals( SMWDataItem $di ) {
		if ( !( $di instanceof SMWDIBlob ) ) {
			return false;
		}

		return $di->getString() === $this->m_string;
	}
}
