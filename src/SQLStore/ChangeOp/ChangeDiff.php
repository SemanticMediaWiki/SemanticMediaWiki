<?php

namespace SMW\SQLStore\ChangeOp;

use Onoi\Cache\Cache;
use SMW\DataItems\WikiPage;
use SMW\Utils\HmacSerializer;

/**
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class ChangeDiff {

	/**
	 * Identifies the cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:store:diff';

	/**
	 * Identifies the cache TTL (one week)
	 */
	const CACHE_TTL = 604800;

	private int $time;

	/**
	 * @var array
	 */
	private $changeList = [];

	/**
	 * @var int
	 */
	private $associatedRev = 0;

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly WikiPage $subject,
		private readonly array $tableChangeOps,
		private readonly array $dataOps,
		private readonly array $propertyList,
		private readonly array $textItems = [],
	) {
		$this->time = time();
	}

	/**
	 * @since 3.1
	 *
	 * @param int $associatedRev
	 */
	public function setAssociatedRev( $associatedRev ): void {
		$this->associatedRev = $associatedRev;
	}

	/**
	 * @since 3.1
	 *
	 * @return int
	 */
	public function getAssociatedRev() {
		return $this->associatedRev;
	}

	/**
	 * @since 3.0
	 *
	 * @return WikiPage
	 */
	public function getSubject(): WikiPage {
		return $this->subject;
	}

	/**
	 * @since 3.0
	 *
	 * @return TableChangeOps[]
	 */
	public function getTableChangeOps(): array {
		return $this->tableChangeOps;
	}

	/**
	 * @since 3.0
	 *
	 * @return TableChangeOps[]
	 */
	public function getDataOps(): array {
		return $this->dataOps;
	}

	/**
	 * @since 3.0
	 *
	 * @return
	 */
	public function getTextItems(): array {
		return $this->textItems;
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $op
	 *
	 * @return
	 */
	public function getPropertyList( $op = false ) {
		if ( $op === true || $op === 'flip' ) {
			$list = [];

			foreach ( $this->propertyList as $key => $value ) {
				if ( is_array( $value ) ) {
					$list[$value['_id']] = $key;
				} else {
					$list[$value] = $key;
				}
			}

			return $list;
		}

		if ( $op === 'id' ) {
			$list = [];

			foreach ( $this->propertyList as $key => $value ) {
				if ( is_array( $value ) ) {
					$list[$value['_id']] = [ '_key' => $key, '_type' => $value['_type'] ];
				}
			}

			return $list;
		}

		return $this->propertyList;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array $changes
	 */
	public function setChangeList( $type, array $changes ): void {
		$this->changeList[$type] = $changes;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function getChangeListByType( $type ) {
		return isset( $this->changeList[$type] ) ? $this->changeList[$type] : [];
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function serialize() {
		return HmacSerializer::compress( $this );
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function toJson( $prettify = false ) {
		$changes = [];

		foreach ( $this->tableChangeOps as $tableChangeOp ) {
			$changes[] = $tableChangeOp->toArray();
		}

		$data = [];

		foreach ( $this->dataOps as $dataOp ) {
			$data[] = $dataOp->toArray();
		}

		$flags = $prettify ? JSON_PRETTY_PRINT : 0;

		return json_encode(
			[
				'time' => $this->time,
				'subject' => $this->subject->getHash(),
				'changes' => $changes,
				'change_list' => $this->changeList,
				'data' => $data,
				'text_items' => $this->textItems,
				'property_list' => $this->propertyList,
				'associated_rev' => $this->associatedRev
			],
			$flags
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 */
	public function save( Cache $cache ): void {
		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			$this->subject->getHash()
		);

		// Keep it a week
		$cache->save( $key, HmacSerializer::compress( $this ), self::CACHE_TTL );
	}

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 * @param WikiPage $subject
	 */
	public static function fetch( Cache $cache, WikiPage $subject ) {
		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			$subject->getHash()
		);

		if ( ( $diff = $cache->fetch( $key ) ) !== false ) {
			return HmacSerializer::uncompress( $diff );
		}

		return false;
	}

}
