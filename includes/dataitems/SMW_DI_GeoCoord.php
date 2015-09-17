<?php

use SMW\DataItemException;

/**
 * Implementation of dataitems that are geographic coordinates.
 *
 * @since 1.6
 *
 * @ingroup SemanticMaps
 *
 * @licence GNU GPL v3
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWDIGeoCoord extends SMWDataItem {

	/**
	 * The locations latitude.
	 *
	 * @since 1.6
	 * @var float
	 */
	protected $latitude;

	/**
	 * The locations longitude.
	 *
	 * @since 1.6
	 * @var float
	 */
	protected $longitude;

	/**
	 * The locations altitude.
	 *
	 * @since 1.7
	 * @var float|null
	 */
	protected $altitude = null;

	/**
	 * Constructor.
	 * Takes a latitude and longitude, and optionally an altitude. These can be provided in 2 forms:
	 * * An associative array with lat, lon and alt keys
	 * * Lat, lon and alt arguments
	 *
	 * The second way to provide the arguments, as well as the altitude argument, where introduced in SMW 1.7.
	 */
	public function __construct() {
		$args = func_get_args();

		$count = count( $args );

		if ( $count === 1 && is_array( $args[0] ) ) {
			if ( array_key_exists( 'lat', $args[0] ) && array_key_exists( 'lon', $args[0] ) ) {
				$this->latitude = (float)$args[0]['lat'];
				$this->longitude = (float)$args[0]['lon'];

				if ( array_key_exists( 'alt', $args[0] ) ) {
					$this->altitude = (float)$args[0]['alt'];
				}
			}
			else {
				throw new DataItemException( 'Invalid coordinate data passed to the SMWDIGeoCoord constructor' );
			}
		}
		elseif ( $count === 2 || $count === 3 ) {
			$this->latitude = (float)$args[0];
			$this->longitude = (float)$args[1];

			if ( $count === 3 ) {
				$this->altitude = (float)$args[2];
			}
		}
		else {
			throw new DataItemException( 'Invalid coordinate data passed to the SMWDIGeoCoord constructor' );
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see SMWDataItem::getDIType()
	 */
	public function getDIType() {
		return SMWDataItem::TYPE_GEO;
	}

	/**
	 * Returns the coordinate set as an array with lat and long (and alt) keys
	 * pointing to float values.
	 *
	 * @since 1.6
	 *
	 * @return array
	 */
	public function getCoordinateSet() {
		$coords = array( 'lat' => $this->latitude, 'lon' => $this->longitude );

		if ( !is_null( $this->altitude ) ) {
			$coords['alt'] = $this->altitude;
		}

		return $coords;
	}

	/**
	 * (non-PHPdoc)
	 * @see SMWDataItem::getSortKey()
	 */
	public function getSortKey() {
		return $this->latitude . ',' . $this->longitude . ( $this->altitude !== null ? ','. $this->altitude : '' );
	}

	/**
	 * (non-PHPdoc)
	 * @see SMWDataItem::getSerialization()
	 */
	public function getSerialization() {
		return implode( ',', $this->getCoordinateSet() );
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
		$count = count( $parts );

		if ( $count !== 2 && $count !== 3 ) {
			throw new DataItemException( 'Unserialization of coordinates failed' );
		}

		$coords = array( 'lat' => (float)$parts[0], 'lon' => (float)$parts[1] );

		if ( $count === 3 ) {
			$coords['alt'] = (float)$parts[2];
		}

		return new self( $coords );
	}

	/**
	 * Returns the latitude.
	 *
	 * @since 1.6
	 *
	 * @return float
	 */
	public function getLatitude() {
		return $this->latitude;
	}

	/**
	 * Returns the longitude.
	 *
	 * @since 1.6
	 *
	 * @return float
	 */
	public function getLongitude() {
		return $this->longitude;
	}

	/**
	 * Returns the altitude if set, null otherwise.
	 *
	 * @since 1.7
	 *
	 * @return float|null
	 */
	public function getAltitude() {
		return $this->altitude;
	}

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_GEO ) {
			return false;
		}

		return $di->getSerialization() === $this->getSerialization();
	}
}
