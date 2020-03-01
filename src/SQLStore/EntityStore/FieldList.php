<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use RuntimeException;

/**
 * @private
 *
 * @license GNU GPL v2
 * @since 3.2
 *
 * @author mwjames
 */
class FieldList {

	/**
	 * List of properties
	 */
	const PROPERTY_LIST = 'list/property';

	/**
	 * List of properties
	 */
	const CATEGORY_LIST = 'list/category';

	/**
	 * @var []
	 */
	private $countMaps = [];

	/**
	 * @since 3.2
	 *
	 * @param iterable $countMaps
	 */
	public function __construct( iterable $countMaps = [] ) {
		$this->countMaps = $countMaps;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function getCountListByType( string $type ) : array {

		$countList = [];

		foreach ( $this->countMaps as $hash => $countMap ) {
			foreach ( $countMap as $id => $map ) {
				foreach ( $map as $key => $counts ) {

					if ( $key === '_SKEY' ) {
						continue;
					}

					if ( $type === self::CATEGORY_LIST && $key === '_INST' ) {
						foreach ( $counts as $k => $count ) {
							if ( !isset( $countList[$k] ) ) {
								$countList[$k] = 0;
							}

							$countList[$k]++;
						}
					} elseif ( $type === self::PROPERTY_LIST && $key !== '_INST' ) {
						if ( !isset( $countList[$key] ) ) {
							$countList[$key] = 0;
						}

						$countList[$key] += $counts;
					}
				}
			}
		}

		return $countList;
	}

	/**
	 * @since 3.2
	 *
	 * @param string|null $type
	 *
	 * @return array
	 */
	public function getHashList( $type = null ) : array {

		$list = [];

		foreach ( $this->countMaps as $hash => $map ) {
			$this->makeList( $map, $list );
		}

		return $list;
	}

	private function makeList( $map, &$list ) {
		foreach ( $map as $id => $values ) {
			foreach ( $values as $key => $counts ) {
				if ( $key === '_INST' ) {
					foreach ( $counts as $k => $count ) {
						$list[$k] = ( new DIWikiPage( $k, NS_CATEGORY ) )->getSha1();
					}
				} else {
					$list[$key] = ( new DIProperty( $key ) )->getSha1();
				}
			}
		}
	}

}
