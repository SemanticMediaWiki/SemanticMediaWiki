<?php

namespace SMW\Exporter\Element;

use InvalidArgumentException;
use RuntimeException;
use SMWDataItem as DataItem;

/**
 * A single resource (individual) for export, as defined by a URI.
 * This class can also be used to represent blank nodes: It is assumed that all
 * objects of class ExpElement or any of its subclasses represent a blank
 * node if their name is empty or of the form "_id" where "id" is any
 * identifier string. IDs are local to the current context, such as a list of
 * triples or an SMWExpData container.
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ExpResource extends ExpElement {

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * @note The given URI must not contain serialization-specific
	 * abbreviations or escapings, such as XML entities.
	 *
	 * @param string $uri The full URI
	 * @param DataItem|null $dataItem
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $uri, DataItem $dataItem = null ) {

		if ( !is_string( $uri ) ) {
			throw new InvalidArgumentException( '$uri needs to be a string' );
		}

		parent::__construct( $dataItem );

		$this->uri = $uri;
	}

	/**
	 * Return true if this resource represents a blank node.
	 *
	 * @return boolean
	 */
	public function isBlankNode() {
		return $this->uri === '' || $this->uri{0} == '_';
	}

	/**
	 * Get the URI of this resource. The result is a UTF-8 encoded URI (or
	 * IRI) without any escaping.
	 *
	 * @return string
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * @since  2.2
	 *
	 * @return array
	 */
	public function getSerialization() {

		$serialization = array(
			'type' => self::TYPE_RESOURCE,
			'uri'  => $this->getUri()
		);

		return $serialization + parent::getSerialization();
	}

	/**
	 * @see ExpElement::newFromSerialization
	 */
	protected static function deserialize( $serialization ) {

		if ( !isset( $serialization['uri'] ) ) {
			throw new RuntimeException( "Invalid serialization format" );
		}

		return new self(
			$serialization['uri'],
			$serialization['dataitem']
		);
	}

}
