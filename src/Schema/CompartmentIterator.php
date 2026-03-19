<?php

namespace SMW\Schema;

use Countable;
use Iterator;
use ReturnTypeWillChange;
use SeekableIterator;
use SMW\Iterators\SeekableIteratorTrait;
use SMW\Utils\DotArray;

/**
 * @license GPL-2.0-or-later
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
	 * @since 3.1
	 */
	public function __construct(
		array $compartments = [],
		private ?string $type = null,
	) {
		$this->container = $compartments;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( string $key ): bool {
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
	#[ReturnTypeWillChange]
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
	public function find( string $key, ?string $flag = null ): CompartmentIterator {
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
