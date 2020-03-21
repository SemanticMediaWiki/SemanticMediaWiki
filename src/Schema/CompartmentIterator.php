<?php

namespace SMW\Schema;

use SeekableIterator;
use Iterator;
use Countable;
use OutOfBoundsException;
use RuntimeException;
use SMW\Utils\DotArray;
use SMW\Iterators\SeekableIteratorTrait;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CompartmentIterator implements Iterator, Countable, SeekableIterator {

	use SeekableIteratorTrait;

	/**
	 * Directly match a specific key without iterating for additional
	 * compartments.
	 */
	const MATCH_KEY = 'match/key';

	/**
	 * Defines a rule compartment type
	 */
	const RULE_COMPARTMENT = 'type/rule';

	/**
	 * @var string|null
	 */
	private $type;

	/**
	 * @since 3.1
	 *
	 * @param array $compartments
	 * @param string|null $type
	 */
	public function __construct( array $compartments = [], ?string $type = null ) {
		$this->container = $compartments;
		$this->type = $type;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( string $key ) : bool {

		foreach ( $this->container as $data ) {
			if ( DotArray::get( $data, $key, false ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see Iterator::current
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function current() {

		$data = current( $this->container );

		if ( $data instanceof Compartment ) {
			return $data;
		}

		if ( !is_array( $data ) ) {
			$data = [ $this->position => $data ];
		}

		if ( $this->type === self::RULE_COMPARTMENT ) {
			return new Rule( $data );
		}

		return new Compartment( $data );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param string|null $flag
	 *
	 * @return CompartmentIterator
	 */
	public function find( string $key, ?string $flag = null ) : CompartmentIterator {

		$meta = [];
		$result = [];

		return new CompartmentIterator(
			$this->search( $key, $flag, $this->container, $meta, $result ),
			$this->type
		);
	}

	private function search( $key, $flag, $data, $meta, &$result ) {

		foreach ( $data as $section => $value ) {

			if ( isset( $data[Compartment::ASSOCIATED_SCHEMA] ) ) {
				$meta[Compartment::ASSOCIATED_SCHEMA] = $data[Compartment::ASSOCIATED_SCHEMA];
			}

			$meta[Compartment::ASSOCIATED_SECTION] = $section;

			if ( $value instanceof Compartment && $value->has( $key ) ) {
				$result[] = $value;
			} elseif ( is_array( $value ) && isset( $value[$key] ) && $flag === self::MATCH_KEY ) {
				$result[] = $value[$key] + $meta;
			} elseif ( is_array( $value ) ) {

				if ( isset( $value[$key] ) ) {
					$result[] = $value + $meta;
				}

				$this->search( $key, $flag, $value, $meta, $result );
			}
		}

		return $result;
	}

}
