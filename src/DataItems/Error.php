<?php

namespace SMW\DataItems;

use MediaWiki\Json\JsonDeserializer;

/**
 * This class implements error list data items. These data items are used to
 * pass around lists of error messages within the application. They are not
 * meant to be stored or exported, but they can be useful to a user.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup DataItems
 */
class Error extends DataItem {

	public ?int $id;

	public function __construct(
		protected $m_errors,
		private $userValue = '',
	) {
	}

	public function getDIType(): int {
		return DataItem::TYPE_ERROR;
	}

	public function getErrors() {
		return $this->m_errors;
	}

	/**
	 * @since 3.0
	 */
	public function getUserValue(): string {
		return $this->userValue;
	}

	public function getSortKey(): string {
		return 'error';
	}

	public function getString(): string {
		return $this->getSerialization();
	}

	public function getSerialization(): string {
		return serialize( $this->m_errors );
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @todo Be more careful with unserialization. It can create E_NOTICEs.
	 */
	public static function doUnserialize( $serialization ): Error {
		return new Error( unserialize( $serialization ) );
	}

	public function equals( DataItem $di ): bool {
		if ( $di->getDIType() !== DataItem::TYPE_ERROR ) {
			return false;
		}

		return $di->getSerialization() === $this->getSerialization();
	}

	/**
	 * Implements \JsonSerializable.
	 *
	 * @since 4.0.0
	 */
	public function jsonSerialize(): array {
		$json = parent::jsonSerialize();
		$json['userValue'] = $this->userValue;
		return $json;
	}

	/**
	 * Implements JsonDeserializable.
	 *
	 * @since 4.0.0
	 *
	 * @return static
	 */
	public static function newFromJsonArray( JsonDeserializer $deserializer, array $json ) {
		$obj = parent::newFromJsonArray( $deserializer, $json );
		$obj->userValue = $json['userValue'];
		return $obj;
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( Error::class, 'SMWDIError' );
