<?php

namespace SMW\DataItems;

use InvalidArgumentException;
use MediaWiki\Json\JsonDeserializable;
use MediaWiki\Json\JsonDeserializer;
use SMW\Options;

/**
 * This group contains all parts of SMW that relate to the processing of dataitems
 * of various types.
 *
 * @defgroup SMWDataItems SMWDataItems
 * @defgroup DataItems DataItems
 * @ingroup SMW
 */

/**
 * Objects of this type represent all that is known about a certain piece of
 * data that could act as the value of some property. Data items only represent
 * the stored data, and are thus at the core of SMW's data model. Data items
 * are always immutable, i.e. they must not be changed after creation (and this
 * is mostly enforced by the API with some minor exceptions).
 *
 * The set of available data items is fixed and cannot be extended. These are
 * the kinds of information that SMW can process. Their concrete use and
 * handling might depend on the context in which they are used. In particular,
 * property values may be influences by settings made for their property. This
 * aspect, however, is not part of the data item API.
 *
 * @since 1.6
 *
 * @ingroup DataItems
 */
abstract class DataItem implements JsonDeserializable {

	/// Data item ID that can be used to indicate that no data item class is appropriate
	const TYPE_NOTYPE = 0;
	/// Data item ID for Number
	const TYPE_NUMBER = 1;
	/// Data item ID for Blob
	const TYPE_BLOB = 2;
	///  Data item ID for Boolean
	const TYPE_BOOLEAN = 4;
	///  Data item ID for Uri
	const TYPE_URI = 5;
	///  Data item ID for TimePoint
	const TYPE_TIME = 6;
	///  Data item ID for GeoCoord
	const TYPE_GEO = 7;
	///  Data item ID for Container
	const TYPE_CONTAINER = 8;
	///  Data item ID for WikiPage
	const TYPE_WIKIPAGE = 9;
	///  Data item ID for Concept
	const TYPE_CONCEPT = 10;
	///  Data item ID for Property
	const TYPE_PROPERTY = 11;
	///  Data item ID for Error
	const TYPE_ERROR = 12;

	private ?Options $options = null;

	/**
	 * Convenience method that returns a constant that defines the concrete
	 * class that implements this data item. Used to switch when processing
	 * data items.
	 * @return int that specifies the basic type of data item
	 */
	abstract public function getDIType();

	/**
	 * Return a value that can be used for sorting data of this type.
	 * If the data is of a numerical type, the sorting must be done in
	 * numerical order. If the data is a string, the data must be sorted
	 * alphabetically.
	 *
	 * @note Every data item returns a sort key, even if there is no
	 * natural linear order for the type. SMW must order listed data
	 * in some way in any case. If there is a natural order (e.g. for
	 * Booleans where false < true), then the sortkey must agree with
	 * this order (e.g. for Booleans where false maps to 0, and true
	 * maps to 1).
	 *
	 * @note Wiki pages are a special case in SMW. They are ordered by a
	 * sortkey that is assigned to them as a property value. When pages are
	 * sorted, this data should be used if possible.
	 *
	 * @return float|string
	 */
	abstract public function getSortKey();

	/**
	 * Method to compare two DataItems
	 * This should result true only if they are of the same DI type
	 * and have the same internal value
	 *
	 * @since 1.8
	 *
	 * @return bool
	 */
	abstract public function equals( DataItem $di );

	/**
	 * Create a data item that represents the sortkey, i.e. either an
	 * Blob or an Number. For efficiency, these subclasses
	 * overwrite this method to return themselves.
	 */
	public function getSortKeyDataItem(): Number|Blob {
		$sortKey = $this->getSortKey();

		if ( is_numeric( $sortKey ) ) {
			return new Number( $sortKey );
		}

		return new Blob( $sortKey );
	}

	/**
	 * Get a UTF-8 encoded string serialization of this data item.
	 * The serialisation should be concise and need not be pretty, but it
	 * must allow unserialization. Each subclass of DataItem implements
	 * a static method doUnserialize() for this purpose.
	 * @return string
	 */
	abstract public function getSerialization();

	/**
	 * @since 3.1
	 */
	public function getSha1(): string {
		return sha1( $this->getSerialization() );
	}

	/**
	 * Get a hash string for this data item. Might be overwritten in
	 * subclasses to obtain shorter or more efficient hashes.
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->getSerialization();
	}

	/**
	 * @since 2.1
	 */
	public function __toString(): string {
		return $this->getSerialization();
	}

	/**
	 * Create a data item of the given dataitem ID based on the the
	 * provided serialization string and (optional) typeid.
	 *
	 * @param int $diType dataitem ID
	 * @param string $serialization
	 *
	 * @return DataItem
	 */
	public static function newFromSerialization( $diType, $serialization ) {
		$diClass = self::getDataItemClassNameForId( $diType );
		return call_user_func( [ $diClass, 'doUnserialize' ], $serialization );
	}

	/**
	 * Gets the class name of the data item that has the provided type id.
	 *
	 * @param int $diType Element of the DataItem::TYPE_ enum
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string
	 */
	public static function getDataItemClassNameForId( $diType ) {
		switch ( $diType ) {
			case self::TYPE_NUMBER:
				return Number::class;
			case self::TYPE_BLOB:
				return Blob::class;
			case self::TYPE_BOOLEAN:
				return Boolean::class;
			case self::TYPE_URI:
				return Uri::class;
			case self::TYPE_TIME:
				return Time::class;
			case self::TYPE_GEO:
				return GeoCoord::class;
			case self::TYPE_CONTAINER:
				return Container::class;
			case self::TYPE_WIKIPAGE:
				return WikiPage::class;
			case self::TYPE_CONCEPT:
				return Concept::class;
			case self::TYPE_PROPERTY:
				return Property::class;
			case self::TYPE_ERROR:
				return Error::class;
			case self::TYPE_NOTYPE:
			default:
				throw new InvalidArgumentException( "The value \"$diType\" is not a valid dataitem ID." );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setOption( $key, $value ): void {
		if ( !$this->options instanceof Options ) {
			$this->options = new Options();
		}

		$this->options->set( $key, $value );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param string|null $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {
		if ( !$this->options instanceof Options ) {
			$this->options = new Options();
		}

		if ( $this->options->has( $key ) ) {
			return $this->options->get( $key );
		}

		return $default;
	}

	/**
	 * Implements \JsonSerializable.
	 *
	 * @since 4.0.0
	 */
	public function jsonSerialize(): array {
		# T312589 explicitly calling jsonSerialize() will be unnecessary
		# in the future.
		return [
			'options' => $this->options ? $this->options->jsonSerialize() : null,
			'value' => $this->getSerialization(),
			'_type_' => get_class( $this ),
		];
	}

	/**
	 * Implements JsonDeserializable.
	 *
	 * @since 4.0.0
	 *
	 * @return static
	 */
	public static function newFromJsonArray( JsonDeserializer $deserializer, array $json ) {
		$obj = static::doUnserialize( $json['value'] );
		$obj->options = $json['options'] ? $deserializer->deserialize( $json['options'] ) : null;
		return $obj;
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( DataItem::class, 'SMWDataItem' );
