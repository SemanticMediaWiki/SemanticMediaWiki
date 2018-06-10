<?php

namespace SMW\MediaWiki\Specials\Browse;

use Html;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\PropertySpecificationLookup;
use SMWDataItem as DataItem;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class GroupFormatter {

	/**
	 * Identifies a group label
	 */
	const MESSAGE_GROUP_LABEL = 'smw-property-group-label-';

	/**
	 * Identifies a group label
	 */
	const MESSAGE_GROUP_DESCRIPTION = 'smw-property-group-description-';

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var boolean
	 */
	private $showGroup = true;

	/**
	 * @var string
	 */
	private $lastGroup = '';

	/**
	 * @var array
	 */
	private $groupLinks = [];

	/**
	 * @since 3.0
	 *
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->propertySpecificationLookup = $propertySpecificationLookup;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $showGroup
	 */
	public function showGroup( $showGroup ) {
		$this->showGroup = $showGroup;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isLastGroup( $group ) {
		return $this->lastGroup === $group;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function hasGroups() {
		return $this->groupLinks !== [];
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$properties
	 */
	public function findGroupMembership( array &$properties ) {

		$groupedProperties = [];
		$this->groupLinks = [];

		foreach ( $properties as $key => $property ) {

			$group = $this->findGroup( $property );

			if ( !isset( $groupedProperties[$group] ) ) {
				$groupedProperties[$group] = [];
			}

			$groupedProperties[$group][] = $property;
		}

		ksort( $groupedProperties, SORT_NATURAL | SORT_FLAG_CASE );
		$properties = $groupedProperties;

		$keys = array_keys( $groupedProperties );
		$this->lastGroup = end( $keys );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $group
	 *
	 * @return string
	 */
	public function getGroupLink( $group ) {

		if ( !isset( $this->groupLinks[$group] ) || $this->groupLinks[$group] === '' ) {
			return $group;
		}

		return Html::rawElement(
			'span',
			[
				'class' => 'group-link'
			],
			$this->groupLinks[$group]
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 * @param DIWikiPage $dataItem
	 *
	 * @return string
	 */
	public function getMessageClassLink( $id, DIWikiPage $dataItem ) {

		$gr = str_replace( '_', ' ', $dataItem->getDBKey() );
		$key = mb_strtolower( str_replace( ' ', '-', $gr ) );

		return Html::rawElement(
			'a',
			[
				'href' => DIWikiPage::newFromText( $id . $key, NS_MEDIAWIKI )->getTitle()->getFullURL(),
				'class' => !Message::exists( $id . $key ) ? 'new' : ''
			],
			$id . $key
		);
	}

	private function findGroup( $property ) {

		if ( $this->showGroup === false ) {
			return '';
		}

		$group = null;

		// Special handling for a `Category` property instance that itself cannot
		// be annotated with a `Is property group` therefor use the fixed
		// `smw-category-group` message to point to a group
		if ( $property->getKey() === '_INST' && Message::exists( 'smw-category-group' ) ) {
			$gr = Message::get( 'smw-category-group' );
		} elseif( ( $group = $this->propertySpecificationLookup->getPropertyGroup( $property ) ) instanceof DataItem ) {
			$gr = str_replace( '_', ' ', $group->getDBKey() );
		} else {
			return '';
		}

		$desc = '';
		$link = '';

		// Convention key to allow a category to transtable using the
		// `smw-group-...` as key and transforms a group `Foo bar` to
		// `smw-group-foo-bar`
		$key = mb_strtolower( str_replace( ' ', '-', $gr ) );

		if ( Message::exists( self::MESSAGE_GROUP_LABEL . $key ) ) {
			$gr = Message::get(
				self::MESSAGE_GROUP_LABEL . $key,
				Message::TEXT,
				Message::USER_LANGUAGE
			);
		}

		if ( Message::exists( self::MESSAGE_GROUP_DESCRIPTION . $key ) ) {
			$desc = Message::get(
				self::MESSAGE_GROUP_DESCRIPTION . $key,
				Message::TEXT,
				Message::USER_LANGUAGE
			);
		}

		if ( $group instanceof DataItem ) {
			$link = Html::rawElement(
				'a',
				[
					'href' => $group->getTitle()->getFullURL()
				],
				$gr
			);
		}

		if ( $desc !== '' ) {
			$link = Html::rawElement(
				'span',
				[
					'class' => 'smw-highlighter smwttinline',
					'data-state' => 'inline'
				],
				$link . Html::rawElement(
					'span',
					[
						'class' => 'smwttcontent'
					],
					$desc
				)
			);
		}

		if ( !isset( $this->groupLinks[$gr] ) || $this->groupLinks[$gr] === '' ) {
			$this->groupLinks[$gr] = $link;
		}

		return $gr;
	}

}
