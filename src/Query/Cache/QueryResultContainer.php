<?php

namespace SMW\Query\Cache;

/**
 * Per-id value object for the durable query-result cache. It holds the cached
 * result payload (`results`/`continue`/`count`/`excerpts`) plus the
 * `@linkedList` set of dependent query ids that `QueryResultStore` enumerates
 * to cascade-purge a subject and all queries embedded on it.
 *
 * This is the SMW-owned replacement for the container value object from the
 * former bundled `onoi/blob-store`. The array payload shape, including the
 * `@linkedList` magic key, is preserved byte-for-byte so entries written by the
 * former store round-trip unchanged.
 *
 * `get()` returns `false` (not `null`) for an absent key, matching the former
 * container so callers can distinguish a stored falsy value from a miss.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class QueryResultContainer {

	/**
	 * Reserved payload key holding the dependent ids as a key set
	 * (`[ $id => true ]`). Frozen: it appears verbatim in serialized cache
	 * entries written by the former blob store.
	 */
	private const LINKED_LIST = '@linkedList';

	private string $id;
	private array $data;
	private int $expiry = 0;

	/**
	 * @since 7.0.0
	 *
	 * @param string $id
	 * @param array $data
	 */
	public function __construct( string $id, array $data = [] ) {
		$this->id = $id;
		$this->data = $data;
	}

	/**
	 * @since 7.0.0
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @since 7.0.0
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * @since 7.0.0
	 */
	public function getExpiry(): int {
		return $this->expiry;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param int $expiry
	 */
	public function setExpiryInSeconds( $expiry ): void {
		$this->expiry = (int)$expiry;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $key
	 */
	public function has( $key ): bool {
		return isset( $this->data[$key] ) || array_key_exists( $key, $this->data );
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $key
	 *
	 * @return mixed The stored value, or `false` when the key is absent.
	 */
	public function get( $key ) {
		return $this->has( $key ) ? $this->data[$key] : false;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ): void {
		$this->data[$key] = $value;
	}

	/**
	 * Record a dependent id so that deleting this container also purges the
	 * linked entry.
	 *
	 * @since 7.0.0
	 *
	 * @param string|int $hash
	 */
	public function addToLinkedList( $hash ): void {
		if ( !isset( $this->data[self::LINKED_LIST] ) ) {
			$this->data[self::LINKED_LIST] = [];
		}

		$this->data[self::LINKED_LIST][$hash] = true;
	}

	/**
	 * @since 7.0.0
	 *
	 * @return array The dependent ids recorded via addToLinkedList().
	 */
	public function getLinkedList(): array {
		return isset( $this->data[self::LINKED_LIST] )
			? array_keys( $this->data[self::LINKED_LIST] )
			: [];
	}

}
