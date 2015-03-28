<?php

namespace SMW\Exporter\Element;

use SMWDataItem as DataItem;
use RuntimeException;
use InvalidArgumentException;

/**
 * A single resource (individual) for export, defined by a URI for which there
 * also is a namespace abbreviation.
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ExpNsResource extends ExpResource {

	/**
	 * Local part of the abbreviated URI
	 * @var string
	 */
	private $localName;

	/**
	 * Namespace URI prefix of the abbreviated URI
	 * @var string
	 */
	private $namespace;

	/**
	 * Namespace abbreviation of the abbreviated URI
	 * @var string
	 */
	private $namespaceId;

	/**
	 * @note The given URI must not contain serialization-specific
	 * abbreviations or escapings, such as XML entities.
	 *
	 * @param string $localName Local part of the abbreviated URI
	 * @param string $namespace Namespace URI prefix of the abbreviated URI
	 * @param string $namespaceId Namespace abbreviation of the abbreviated URI
	 * @param DataItem|null $dataItem
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $localName, $namespace, $namespaceId, DataItem $dataItem = null ) {

		if ( !is_string( $localName ) ) {
			throw new InvalidArgumentException( '$localName needs to be a string' );
		}

		if ( !is_string( $namespace ) ) {
			throw new InvalidArgumentException( '$namespace needs to be a string' );
		}

		if ( !is_string( $namespaceId ) ) {
			throw new InvalidArgumentException( '$namespaceId needs to be a string' );
		}

		parent::__construct( $namespace . $localName, $dataItem );

		$this->localName = $localName;
		$this->namespace = $namespace;
		$this->namespaceId = $namespaceId;
	}

	/**
	 * Return a qualified name for the element.
	 *
	 * @return string
	 */
	public function getQName() {
		return $this->namespaceId . ':' . $this->localName;
	}

	/**
	 * Get the namespace identifier used (the part before :).
	 *
	 * @return string
	 */
	public function getNamespaceId() {
		return $this->namespaceId;
	}

	/**
	 * Get the namespace URI that is used in the abbreviation.
	 *
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * Get the local name (the part after :).
	 *
	 * @return string
	 */
	public function getLocalName() {
		return $this->localName;
	}

	/**
	 * Check if the local name is qualifies as a local name in XML and
	 * Turtle. The function returns true if this is surely the case, and
	 * false if it may not be the case. However, we do not check the whole
	 * range of allowed Unicode entities for performance reasons.
	 *
	 * @return boolean
	 */
	public function hasAllowedLocalName() {
		return preg_match( '/^[A-Za-z_][-A-Za-z_0-9]*$/u', $this->localName );
	}

	/**
	 * @since  2.2
	 *
	 * @return array
	 */
	public function getSerialization() {

		// Use '|' as divider as it is unlikely that symbol appears within a uri
		$serialization = array(
			'type' => self::TYPE_NSRESOURCE,
			'uri'  => $this->localName . '|' . $this->namespace . '|' . $this->namespaceId
		);

		return $serialization + parent::getSerialization();
	}

	/**
	 * @see ExpElement::newFromSerialization
	 */
	protected static function deserialize( $serialization ) {

		if ( !isset( $serialization['uri'] ) ) {
			throw new RuntimeException( "Invalid serialization format, missing a uri element" );
		}

		if ( substr_count( $serialization['uri'], '|') < 2 ) {
			throw new RuntimeException( "Invalid uri format, expected two '|' dividers" );
		}

		list( $localName, $namespace, $namespaceId ) = explode( '|', $serialization['uri'], 3 );

		return new self(
			$localName,
			$namespace,
			$namespaceId,
			$serialization['dataitem']
		);
	}

}
