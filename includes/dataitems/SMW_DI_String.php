<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * Exception to be thrown when input string is too long.
 * 
 * @since 1.6
 */
class SMWStringLengthException extends SMWDataItemException {
	protected $m_maxlength;

	public function __construct( $string, $maxlength ) {
		parent::__construct( 'String "' . mb_substr( $string, 0, 10 ) . '...' . mb_substr( $string, mb_strlen( $string ) - 10 ) . ' exceeds length limit for strings. Use SMWDIBlob for long strings.'  );
		$this->m_maxlength = $maxlength;
	}

	/**
	 * Get the maximum length that the string is allowed to have. The length
	 * is counted with str_len, i.e. multibyte (UTF-8) strings are ignored.
	 */
	public function getMaxLength() {
		return $this->m_maxlength;
	}
}

/**
 * This class implements (length restricted) string data items.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIString extends SMWDIBlob {

	const MAXLENGTH = 255;

	/**
	 * Constructor.
	 *
	 * @throws SMWStringLengthException if the string is longer than
	 * SMWDIString::MAXLENGTH. The bytes are counted, not the (possibly
	 * multibyte) glyphs of UTF-8, since we care about byte length in (any)
	 * storage backend.
	 */
	public function __construct( $string ) {
		if ( strlen( $string ) > SMWDIString::MAXLENGTH ) {
			throw new SMWStringLengthException( $string, SMWDIString::MAXLENGTH );
		}
		parent::__construct( $string );
	}

	public function getDIType() {
		return SMWDataItem::TYPE_STRING;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDIString
	 */
	public static function doUnserialize( $serialization ) {
		return new SMWDIString( $serialization );
	}

}
