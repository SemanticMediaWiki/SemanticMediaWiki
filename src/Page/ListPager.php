<?php

namespace SMW\Page;

use SMWDataItem as DataItem;
use SMW\Localizer;
use Html;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListPager {

	/**
	 * @var string
	 */
	public static $language = '';

	/**
	 * @since 2.4
	 */
	public static function getLinks( Title $title, $limit, $offset = 0, $count = 0, array $query = array() ) {

		$navigation = '';

		$navigation = self::getPagingLinks(
			$title,
			$limit,
			$offset,
			$count,
			$query
		);

		return $navigation;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title   $title
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return string
	 */
	public static function filterInput( Title $title, $limit = 0, $offset = 0, $filter = '' ) {

		$form = \Xml::tags(
			'form',
			[
				'id'     => 'search',
				'name'   => 'foo',
				'action' => $GLOBALS['wgScript']
			],
			Html::hidden(
			'title',
			strtok( $title->getPrefixedText(), '/' )
			) . Html::hidden(
				'limit',
				$limit
			) . Html::hidden(
				'offset',
				$offset
			)
		);

		$label = wfMessage( 'smw-list-pager-filter' )->text();

		$form .= Html::rawElement(
			'label',
			[],
			$label . Html::rawElement(
				'input',
				[
					'type' => 'search',
					'name' => 'filter',
					'value' => $filter,
					'form' => 'search'
				]
			)
		);

		return Html::rawElement(
			'div',
			[
				'id' => 'list-pager',
				'class' => 'list-pager-value-filter'
			],
			$form
		);
	}

	/**
	 * Generate (prev x| next x) (20|50|100...) type links for paging
	 *
	 * @param Title $title Title object to link
	 * @param int $offset
	 * @param int $limit
	 * @param integer $count
	 * @param array $query Optional URL query parameter string
	 * @return string
	 */
	public static function getPagingLinks( Title $title, $limit, $offset, $count = 0, array $query = array() ) {

		$list = [];
		$limit = (int)$limit;
		$offset = (int)$offset;
		$count = (int)$count;

		$atend = $count < $limit;
		$disabled = $count > 0 ? '' : ' disabled';

		if ( self::$language === '' ) {
			$language = Localizer::getInstance()->getUserLanguage();
		} else {
			$language = Localizer::getInstance()->getLanguage( self::$language );
		}

		# Make 'previous' link
		$prev = wfMessage( 'prevn' )->inLanguage( $language )->title( $title )->numParams( $limit )->text();

		if ( $offset > 0 ) {
			$plink = self::numLink( $title, max( $offset - $limit, 0 ), $limit, $query, $prev, 'prevn-title', 'mw-prevlink', $disabled, $language );
		} else {
			$plink = htmlspecialchars( $prev );
		}

		# Make 'next' link
		$next = wfMessage( 'nextn' )->inLanguage( $language )->title( $title )->numParams( $limit )->text();

		if ( $atend ) {
			$nlink = htmlspecialchars( $next );
		} else {
			$nlink = self::numLink( $title, $offset + $limit, $limit, $query, $next, 'nextn-title', 'mw-nextlink', $disabled, $language );
		}

		# Make links to set number of items per page

		foreach ( [ 20, 50, 100, 250, 500 ] as $num ) {
			$list[] = self::numLink(
				$title,
				$offset,
				$num,
				$query,
				$language->formatNum( $num ),
				'shown-title',
				'mw-numlink',
				$disabled,
				$language
			);
		}

		return wfMessage( 'viewprevnext' )->inLanguage( $language )->title( $title )->rawParams( $plink, $nlink, $language->pipeList( $list ) )->escaped();
	}

	/**
	 * Helper function for viewPrevNext() that generates links
	 *
	 * @param Title $title Title object to link
	 * @param int $offset
	 * @param int $limit
	 * @param array $query Extra query parameters
	 * @param string $link Text to use for the link; will be escaped
	 * @param string $tooltipMsg Name of the message to use as tooltip
	 * @param string $class Value of the "class" attribute of the link
	 * @return string HTML fragment
	 */
	private static function numLink( Title $title, $offset, $limit, array $query, $link, $tooltipMsg, $class, $disabled, $language ) {
		$query = [ 'limit' => $limit, 'offset' => $offset ] + $query;

		$tooltip = wfMessage( $tooltipMsg )->inLanguage( $language )->title( $title )->numParams( $limit )->text();

		return Html::element( 'a',
			[
				'href' => $title->getLocalURL( $query ),
				'title' => $tooltip,
			],
			$link
		);
	}

}
