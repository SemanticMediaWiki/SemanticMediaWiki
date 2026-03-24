<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DataItems\Property;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ListAugmentor {

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$res
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function augment( array &$res, array $parameters ): ?array {
		if ( !isset( $res['query'] ) && $res['query'] === [] ) {
			return null;
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

		if ( $type === 'property' && isset( $parameters['description'] ) ) {
			$this->addPropertyDescription( $res, $lang );
		}

		if ( $type === 'property' && isset( $parameters['prefLabel'] ) ) {
			$this->addPreferredPropertyLabel( $res, $lang );
		}

		if ( $type === 'property' && isset( $parameters['usageCount'] ) ) {
			$this->addUsageCount( $res );
		}

		// Remove the internal ID, no external consumer should rely on it
		foreach ( $res['query'] as $key => &$value ) {
			unset( $value['id'] );
		}

		return $res;
	}

	private function addUsageCount( array &$res ): void {
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

	private function addPreferredPropertyLabel( array &$res, array $languageCodes ): void {
		$list = $res['query'];

		foreach ( $list as $key => $value ) {
			$property = new Property( $key );
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

	private function addPropertyDescription( array &$res, array $languageCodes ): void {
		$list = $res['query'];
		$propertySpecificationLookup = ApplicationFactory::getInstance()->getPropertySpecificationLookup();

		foreach ( $list as $key => $value ) {
			$property = new Property( $key );
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
