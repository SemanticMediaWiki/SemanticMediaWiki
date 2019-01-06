<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\DIProperty;
use Exception;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\StringCondition;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PSubjectLookup extends Lookup {

	const VERSION = 1;

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
	 * @return string|integer
	 */
	public function getVersion() {
		return __METHOD__ . self::VERSION;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function lookup( array $parameters ) {

		$limit = 20;
		$offset = 0;

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$list = [];
		$continueOffset = 0;
		$property = null;
		$value = null;

		if ( isset( $parameters['property'] ) ) {
			$property = $parameters['property'];

			// Get the last which represents the final output
			// Foo.Bar.Foobar.Baz
			if ( strpos( $property, '.' ) !== false ) {
				$chain = explode( '.', $property );
				$property = array_pop( $chain );
			}
		}

		if ( isset( $parameters['value'] ) ) {
			$value = $parameters['value'];
		}

		if ( $property === '' || $property === null ) {
			return [];
		}

		list( $list, $continueOffset ) = $this->findPropertySubjects(
			$property,
			$value,
			$limit,
			$offset,
			$parameters
		);

		// Changing this output format requires to set a new version
		$res = [
			'query' => $list,
			'query-continue-offset' => $continueOffset,
			'version' => self::VERSION,
			'meta' => [
				'type'  => 'psubject',
				'limit' => $limit,
				'count' => count( $list )
			]
		];

		return $res;
	}

	private function findPropertySubjects( $property, $value, $limit, $offset, $parameters ) {

		$list = [];
		$dataItem = null;

		$property = DIProperty::newFromUserLabel( $property );

		if ( $value !== '' && $value !== null ) {
			$dataItem = DataValueFactory::getInstance()->newDataValueByProperty( $property, $value )->getDataItem();
		}

		$continueOffset = 0;
		$count = 0;
		$requestOptions = $this->newRequestOptions( $parameters );

		$res = $this->store->getPropertySubjects(
			$property,
			$dataItem,
			$requestOptions
		);

		foreach ( $res as $dataItem ) {

			if ( !$dataItem instanceof DIWikiPage ) {
				continue;
			}

			if ( isset( $parameters['title-prefix'] ) && (bool)$parameters['title-prefix'] === false ) {
				$list[] = $dataItem->getTitle()->getText();
			} else {
				$list[] = $dataItem->getTitle()->getPrefixedText();
			}
		}

		if ( $this->is_iterable( $res ) ) {
			$count = count( $res );
		}

		if ( $count > $limit ) {
			$continueOffset = $offset + $count;
			array_pop( $list );
		}

		return [ $list, $continueOffset ];
	}

	private function newRequestOptions( $parameters ) {

		$limit = 20;
		$offset = 0;
		$search = '';

		if ( isset( $parameters['limit'] ) ) {
			$limit = (int)$parameters['limit'];
		}

		if ( isset( $parameters['offset'] ) ) {
			$offset = (int)$parameters['offset'];
		}

		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;
		$requestOptions->setLimit( $limit + 1 );
		$requestOptions->setOffset( $offset );

		if ( isset( $parameters['search'] ) && $parameters['search'] !== '' ) {
			$search = $parameters['search'];

			if ( $search !== '' && $search{0} !== '_' ) {
				$search = str_replace( "_", " ", $search );
			}

			$requestOptions->addStringCondition(
				$search,
				StringCondition::STRCOND_MID
			);

			// Disjunctive condition to allow for auto searches to match foaf OR Foaf
			$requestOptions->addStringCondition(
				ucfirst( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			// Allow something like FOO to match the search string `foo`
			$requestOptions->addStringCondition(
				strtoupper( $search ),
				StringCondition::STRCOND_MID,
				true
			);

			$requestOptions->addStringCondition(
				strtolower( $search ),
				StringCondition::STRCOND_MID,
				true
			);
		}

		return $requestOptions;
	}

	private function is_iterable( $obj ) {
		return is_array( $obj ) || ( is_object( $obj ) && ( $obj instanceof \Traversable ) );
	}

}
