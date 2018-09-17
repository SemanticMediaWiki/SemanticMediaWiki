<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DIProperty;
use SMW\Query\ProfileAnnotator;
use SMWDIBlob as DIBlob;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ParametersProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @var Query
	 */
	private $query;

	/**
	 * @since 2.5
	 *
	 * @param ProfileAnnotator $profileAnnotator
	 * @param Query $query
	 */
	public function __construct( ProfileAnnotator $profileAnnotator, Query $query ) {
		parent::__construct( $profileAnnotator );
		$this->query = $query;
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {

		list( $sort, $order ) = $this->doSerializeSortKeys( $this->query );

		$options = [
			'limit'  => $this->query->getLimit(),
			'offset' => $this->query->getOffset(),
			'sort'   => $sort,
			'order'  => $order,
			'mode'   => $this->query->getQueryMode()
		];

		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_ASKPA' ),
			new DIBlob( json_encode( $options ) )
		);
	}

	private function doSerializeSortKeys( $query ) {

		$sort = [];
		$order = [];

		if ( $query->getSortKeys() === null ) {
			return [ $sort, $order ];
		}

		foreach ( $query->getSortKeys() as $key => $value ) {
			$sort[] = $key;
			$order[] = strtolower( $value );
		}

		return [ $sort, $order ];
	}

}
