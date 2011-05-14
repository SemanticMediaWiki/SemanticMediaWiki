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
	 * @param array $coords Array with lat and long keys pointing to float values.
	 * @param string $typeid
	 */
	public function __construct( array $coords, $typeid = '_geo' ) {
		parent::__construct( $typeid );

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
	 * Returns the coordinate set as an array with lat and long keys pointing to float values.
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
	 * @return SMWDINumber
	 */
	public static function doUnserialize( $serialization, $typeid = '_geo' ) {
		$parts = explode( ',', $serialization );
		
		if ( count( $parts ) != 2 ) {
			throw new Exception( 'Unserialization of coordinates failed' );
		}
		
		return new self( array( 'lat' => (float)$parts[0], 'lon' => (float)$parts[1], ), $typeid );
	}

}
