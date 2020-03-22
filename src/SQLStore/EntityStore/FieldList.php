<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use RuntimeException;
use SMW\Exception\PredefinedPropertyLabelMismatchException;

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
					$this->matchKeyByCounts( $type, $key, $counts, $countList );
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
				$this->matchKeyByHash( $key, $counts, $list );
			}
		}
	}

	private function matchKeyByHash( $key, $counts, &$list ) {

		// It is an internal property, so we never reference it as part of a
		// lookup!
		if ( $key === '_SKEY' ) {
			return;
		}

		if ( $key === '_INST' ) {
			foreach ( $counts as $k => $count ) {
				// @see DIWikiPage::getSha1
				$list[$k] = sha1( json_encode( [ $k, NS_CATEGORY, '', '' ] ) );
			}
		} else {
			// @see DIProperty::getSha1
			$list[$key] = sha1( json_encode( [ $key, SMW_NS_PROPERTY, '', '' ] ) );
		}
	}

	private function matchKeyByCounts( $type, $key, $counts, &$list ) {

		if ( $key === '_SKEY' ) {
			return;
		}

		if ( $type === self::CATEGORY_LIST && $key === '_INST' ) {
			foreach ( $counts as $k => $count ) {
				if ( !isset( $list[$k] ) ) {
					$list[$k] = 0;
				}

				$list[$k]++;
			}
		} elseif ( $type === self::PROPERTY_LIST && $key !== '_INST' ) {
			if ( !isset( $list[$key] ) ) {
				$list[$key] = 0;
			}

			$list[$key] += $counts;
		}
	}

}
