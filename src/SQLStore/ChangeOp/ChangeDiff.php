<?php

namespace SMW\SQLStore\ChangeOp;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\Utils\HmacSerializer;

/**
 * @license GNU GPL v2+
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

	/**
	 * @var string
	 */
	private $time;

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @var array
	 */
	private $tableChangeOps = [];

	/**
	 * @var array
	 */
	private $dataOps = [];

	/**
	 * @var array
	 */
	private $propertyList = [];

	/**
	 * @var array
	 */
	private $textItems = [];

	/**
	 * @var array
	 */
	private $changeList = [];

	/**
	 * @var integer
	 */
	private $associatedRev = 0;

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 * @param array $tableChangeOps
	 * @param array $dataOps
	 * @param array $propertyList
	 * @param array $textItems
	 */
	public function __construct( DIWikiPage $subject, array $tableChangeOps, array $dataOps, array $propertyList, array $textItems = [] ) {
		$this->time = time();
		$this->subject = $subject;
		$this->tableChangeOps = $tableChangeOps;
		$this->dataOps = $dataOps;
		$this->propertyList = $propertyList;
		$this->textItems = $textItems;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $associatedRev
	 */
	public function setAssociatedRev( $associatedRev ) {
		$this->associatedRev = $associatedRev;
	}

	/**
	 * @since 3.1
	 *
	 * @return integer
	 */
	public function getAssociatedRev() {
		return $this->associatedRev;
	}

	/**
	 * @since 3.0
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @since 3.0
	 *
	 * @return TableChangeOps[]
	 */
	public function getTableChangeOps() {
		return $this->tableChangeOps;
	}

	/**
	 * @since 3.0
	 *
	 * @return TableChangeOps[]
	 */
	public function getDataOps() {
		return $this->dataOps;
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getTextItems() {
		return $this->textItems;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $op
	 *
	 * @return []
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
					$list[$value['_id']] = [ '_key' => $key, '_type'=> $value['_type'] ];
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
	public function setChangeList( $type, array $changes ) {
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
	public function save( Cache $cache ) {

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
	 * @param DIWikiPage $subject
	 */
	public static function fetch( Cache $cache, DIWikiPage $subject ) {

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
