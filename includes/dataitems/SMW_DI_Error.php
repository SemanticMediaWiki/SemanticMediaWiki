<?php
/**
 * @ingroup SMWDataItems
 */

use MediaWiki\Json\JsonUnserializer;

/**
 * This class implements error list data items. These data items are used to
 * pass around lists of error messages within the application. They are not
 * meant to be stored or exported, but they can be useful to a user.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDIError extends SMWDataItem {

	/**
	 * List of error messages. Should always be safe for HTML.
	 * @var array of strings
	 */
	protected $m_errors;

	/**
	 * @var string
	 */
	private $userValue;

	public function __construct( $errors, $userValue = '' ) {
		$this->m_errors = $errors;
		$this->userValue = $userValue;
	}

	public function getDIType() {
		return SMWDataItem::TYPE_ERROR;
	}

	public function getErrors() {
		return $this->m_errors;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getUserValue() {
		return $this->userValue;
	}

	public function getSortKey() {
		return 'error';
	}

	public function getString() {
		return $this->getSerialization();
	}

	public function getSerialization() {
		return serialize( $this->m_errors );
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @todo Be more careful with unserialization. It can create E_NOTICEs.
	 * @return SMWDIError
	 */
	public static function doUnserialize( $serialization ) {
		return new SMWDIError( unserialize( $serialization ) );
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_ERROR ) {
			return false;
		}

		return $di->getSerialization() === $this->getSerialization();
	}

	/**
	 * Implements \JsonSerializable.
	 * 
	 * @since 4.0.0
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		$json = parent::jsonSerialize();
		$json['userValue'] = $this->userValue;
		return $json;
	}

	/**
	 * Implements JsonUnserializable.
	 * 
	 * @since 4.0.0
	 *
	 * @param JsonUnserializer $unserializer Unserializer
	 * @param array $json JSON to be unserialized
	 *
	 * @return self
	 */
	public static function newFromJsonArray( JsonUnserializer $unserializer, array $json ) {
		$obj = parent::newFromJsonArray( $unserializer, $json );
		$obj->userValue = $json['userValue'];
		return $obj;
	}

}
