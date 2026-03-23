<?php

namespace SMW\Query\ProfileAnnotators;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\Query\ProfileAnnotator;
use SMW\Query\Query;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ParametersProfileAnnotator extends ProfileAnnotatorDecorator {

	/**
	 * @since 2.5
	 */
	public function __construct(
		ProfileAnnotator $profileAnnotator,
		private readonly Query $query,
	) {
		parent::__construct( $profileAnnotator );
	}

	/**
	 * ProfileAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		[ $sort, $order ] = $this->doSerializeSortKeys( $this->query );

		$options = [
			'limit'  => $this->query->getLimit(),
			'offset' => $this->query->getOffset(),
			'sort'   => $sort,
			'order'  => $order,
			'mode'   => $this->query->getQueryMode()
		];

		$this->getSemanticData()->addPropertyObjectValue(
			new Property( '_ASKPA' ),
			new Blob( json_encode( $options ) )
		);
	}

	/**
	 * @return mixed[][]
	 */
	private function doSerializeSortKeys( Query $query ): array {
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
