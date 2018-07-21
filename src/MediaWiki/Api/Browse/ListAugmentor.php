<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListAugmentor {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$res
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function augment( array &$res, array $parameters ) {

		if ( !isset( $res['query'] ) && $res['query'] === [] ) {
			return;
		}

		$type = null;
		$lang = 'en';

		if ( isset( $res['meta']['type'] ) ) {
			$type = $res['meta']['type'];
		}

		if ( isset( $parameters['lang'] ) ) {
			$lang = $parameters['lang'];
		}

		if ( is_string( $lang ) ) {
			$lang = [ $lang ];
		}

		if ( $type === 'property' && isset( $parameters['description' ] ) ) {
			$this->addPropertyDescription( $res, $lang );
		}

		if ( $type === 'property' && isset( $parameters['prefLabel' ] ) ) {
			$this->addPreferredPropertyLabel( $res, $lang );
		}

		if ( $type === 'property' && isset( $parameters['usageCount' ] ) ) {
			$this->addUsageCount( $res );
		}

		// Remove the internal ID, no external consumer should rely on it
		foreach ( $res['query'] as $key => &$value ) {
			unset( $value['id'] );
		}

		return $res;
	}

	private function addUsageCount( &$res ) {

		$list = $res['query'];

		$db = $this->store->getConnection( 'mw.db' );

		foreach ( $list as $key => $value ) {

			$row = $db->selectRow(
				SQLStore::PROPERTY_STATISTICS_TABLE,
				[ 'usage_count' ],
				[
					'p_id' => $value['id']
				],
				__METHOD__
			);

			$list[$key] = $value + [
				'usageCount' => $row->usage_count
			];
		}

		$res['query'] = $list;
	}

	private function addPreferredPropertyLabel( &$res, array $languageCodes ) {

		$list = $res['query'];

		foreach ( $list as $key => $value ) {
			$property = new DIProperty( $key );
			$prefLabel = [];

			foreach ( $languageCodes as $code ) {
				$prefLabel[$code] = $property->getPreferredLabel( $code );
			}

			$list[$key] = $value + [
				'prefLabel' => $prefLabel
			];
		}

		$res['query'] = $list;
	}

	private function addPropertyDescription( &$res, array $languageCodes ) {

		$list = $res['query'];
		$propertySpecificationLookup = ApplicationFactory::getInstance()->getPropertySpecificationLookup();

		foreach ( $list as $key => $value ) {
			$property = new DIProperty( $key );
			$description = [];

			foreach ( $languageCodes as $code ) {
				$description[$code] = $propertySpecificationLookup->getPropertyDescriptionByLanguageCode( $property, $code );
			}

			$list[$key] = $value + [
				'description' => $description
			];
		}

		$res['query'] = $list;
	}

}
