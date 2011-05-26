<?php

/**
 * Implementation of dataitems that are geographic coordinates.
 *
 * @since 1.6
 *
 * @file SMW_DI_GeoCoord.php
 * @ingroup SemanticMaps
 *
 * @licence GNU GPL v3
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWDIGeoCoord extends SMWDataItem {

	protected $coordinateSet;

	/**
	 * Constructor.
	 * 
	 * @param $coords array Array with lat and long keys pointing to float values.
	 * @param $typeid string
	 */
	public function __construct( array $coords ) {
		$this->coordinateSet = $coords;
	}

	/**
	 * (non-PHPdoc)
	 * @see SMWDataItem::getDIType()
	 */
	public function getDIType() {
		return SMWDataItem::TYPE_GEO;
	}
	
	/**
	 * Returns the coordinate set as an array with lat and long keys
	 * pointing to float values.
	 * 
	 * @since 1.6
	 *
	 * @return array
	 */
	public function getCoordinateSet() {
		return $this->coordinateSet;
	}

	/**
	 * (non-PHPdoc)
	 * @see SMWDataItem::getSortKey()
	 */
	public function getSortKey() {
		return $this->coordinateSet['lat']; // TODO
	}

	/**
	 * (non-PHPdoc)
	 * @see SMWDataItem::getSerialization()
	 */
	public function getSerialization() {
		return $this->coordinateSet['lat'] . ',' . $this->coordinateSet['lon'];
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @note PHP can convert any string to some number, so we do not do
	 * validation here (because this would require less efficient parsing).
	 * 
	 * @since 1.6
	 * 
	 * @param string $serialization
	 * 
	 * @return SMWDIGeoCoord
	 */
	public static function doUnserialize( $serialization ) {
		$parts = explode( ',', $serialization );

		if ( count( $parts ) != 2 ) {
			throw new SMWDataItemException( 'Unserialization of coordinates failed' );
		}

		return new self( array( 'lat' => (float)$parts[0], 'lon' => (float)$parts[1], ) );
	}
	
	/**
	 * Returns the latitude.
	 * 
	 * @since 1.6
	 * 
	 * @return float
	 */
	public function getLatitude() {
		return $this->coordinateSet['lat'];
	}
	
	/**
	 * Returns the longitude.
	 * 
	 * @since 1.6
	 * 
	 * @return float
	 */
	public function getLongitude() {
		return $this->coordinateSet['lon'];
	}

}
