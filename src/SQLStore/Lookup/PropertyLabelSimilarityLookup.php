<?php

namespace SMW\SQLStore\Lookup;

use Exception;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\PropertySpecificationLookup;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyLabelSimilarityLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var integer/float
	 */
	private $threshold = 50;

	/**
	 * @var DIProperty|null
	 */
	private $exemptionProperty;

	/**
	 * @var integer
	 */
	private $lookupCount = 0;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param PropertySpecificationLookup|null $propertySpecificationLookup
	 */
	public function __construct( Store $store, PropertySpecificationLookup $propertySpecificationLookup = null ) {
		$this->store = $store;
		$this->propertySpecificationLookup = $propertySpecificationLookup;

		if ( $this->propertySpecificationLookup === null ) {
			$this->propertySpecificationLookup = ApplicationFactory::getInstance()->getPropertySpecificationLookup();
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $threshold
	 *
	 * @return boolean
	 */
	public function setThreshold( $threshold ) {
		$this->threshold = $threshold;
	}

	/**
	 * @note A property that when annotated as part of a property specification
	 * will be used as exemption marker during the similarity comparison.
	 *
	 * @since 2.5
	 *
	 * @param string $exemptionProperty
	 */
	public function setExemptionProperty( $exemptionProperty ) {

		if ( $exemptionProperty === '' ) {
			return;
		}

		$this->exemptionProperty = DataValueFactory::getInstance()->newPropertyValueByLabel( $exemptionProperty )->getDataItem();
	}

	/**
	 * @since 2.5
	 *
	 * @return DIProperty|null
	 */
	public function getExemptionProperty() {
		return $this->exemptionProperty;
	}

	/**
	 * @since 2.5
	 *
	 * @return integer
	 */
	public function getLookupCount() {
		return $this->lookupCount;
	}

	/**
	 * @since 3.0
	 *
	 * @return integer
	 */
	public function getPropertyMaxCount() {
		$statistics = $this->store->getStatistics();

		if ( isset( $statistics['TOTALPROPS'] ) ) {
			return $statistics['TOTALPROPS'];
		}

		return 0;
	}

	/**
	 * @since 2.5
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return array
	 */
	public function compareAndFindLabels( RequestOptions $requestOptions = null ) {

		$withType = false;
		$propertyList = $this->getPropertyList( $requestOptions );

		if ( $requestOptions !== null ) {
			foreach ( $requestOptions->getExtraConditions() as $extraCondition ) {
				if ( isset( $extraCondition['type'] ) ) {
					$withType = $extraCondition['type'];
				}
			}
		}

		$this->lookupCount = count( $propertyList );
		$similarities = $this->matchLabels( $propertyList, $withType );

		usort( $similarities, function ( $a, $b ) {
			return $a['similarity'] < $b['similarity'];
		} );

		return $similarities;
	}

	private function matchLabels( $propertyList, $withType ) {

		$similarities = [];
		$lookupComplete = [];

		foreach ( $propertyList as $first ) {

			if ( !$first->isUserDefined() ) {
				continue;
			}

			foreach ( $propertyList as $second ) {

				// Was already completed when used as first element
				if ( isset( $lookupComplete[$second->getKey()] ) ) {
					continue;
				}

				if ( $first->getKey() === $second->getKey() || !$second->isUserDefined() ) {
					continue;
				}

				$hash = $this->getHash( $first, $second );

				if ( $this->isExempted( $first, $second ) || isset( $similarities[$hash] ) ) {
					continue;
				}

				$percent = '';

				similar_text( $first->getLabel(), $second->getLabel(), $percent );

				if ( $percent >= $this->threshold ) {
					$similarities[$hash] = $this->getSummary( $first, $second, $percent, $withType );
				}
			}

			$lookupComplete[$first->getKey()] = true;
		}

		return $similarities;
	}

	/**
	 * @since 2.5
	 *
	 * @param  DIProperty $first
	 * @param  DIProperty $second
	 *
	 * @return boolean
	 */
	private function isExempted( DIProperty $first, DIProperty $second ) {

		if ( $this->exemptionProperty === null ) {
			return false;
		}

		$definedBy = $this->propertySpecificationLookup->getSpecification(
			$first,
			$this->exemptionProperty
		);

		foreach ( $definedBy as $dataItem ) {
			if ( $dataItem->equals( $second->getCanonicalDiWikiPage() ) ) {
				return true;
			}
		}

		$definedBy = $this->propertySpecificationLookup->getSpecification(
			$second,
			$this->exemptionProperty
		);

		foreach ( $definedBy as $dataItem ) {
			if ( $dataItem->equals( $first->getCanonicalDiWikiPage() ) ) {
				return true;
			}
		}

		return false;
	}

	private function getHash( DIProperty $first, DIProperty $second ) {

		$hashing = [];
		$hashing[] = $first->getKey();
		$hashing[] = $second->getKey();

		sort( $hashing );

		return md5( implode( '', $hashing ) );
	}

	private function getSummary( DIProperty $first, DIProperty $second, $percent, $withType ) {

		$summary = [];

		if ( $withType ) {
			$summary[] = [
				'label' => $first->getLabel(),
				'type'  => $first->findPropertyTypeID()
			];
		} else {
			$summary[] = $first->getLabel();
		}

		if ( $withType ) {
			$summary[] = [
				'label' => $second->getLabel(),
				'type'  => $second->findPropertyTypeID()
			];
		} else {
			$summary[] = $second->getLabel();
		}

		return [
			'property'   => $summary,
			'similarity' => round( $percent, 2 )
		];
	}

	private function getPropertyList( RequestOptions $requestOptions = null ) {

		$propertyList = [];

		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$options = [ 'ORDER BY' => 'smw_sort' ];

		$conditions = [
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		if ( $requestOptions !== null && $requestOptions->getLimit() > 0 ) {
			$options['LIMIT'] = $requestOptions->getLimit();
			$options['OFFSET'] = max( $requestOptions->getOffset(), 0 );
		}

		if ( $requestOptions !== null && $requestOptions->getStringConditions() ) {
			$conditions[] = $this->store->getSQLConditions( $requestOptions, '', 'smw_sortkey', false );
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$res = $connection->select(
			SQLStore::ID_TABLE,
			[ 'smw_id', 'smw_title' ],
			$conditions,
			__METHOD__,
			$options
		);

		foreach ( $res as $row ) {

			try {
				$propertyList[] = new DIProperty( str_replace( ' ', '_', $row->smw_title ) );
			} catch ( Exception $e ) {
				// Do nothing ...
			}
		}

		return $propertyList;
	}

}
