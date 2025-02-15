<?php

namespace SMW\Utils;

use Html;
use SMW\Highlighter;
use SMW\Localizer;
use SMW\Message;
use Title;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Pager {

	/**
	 * @var string
	 */
	public static $language = '';

	/**
	 * @since 2.4
	 */
	public static function pagination( Title $title, $limit, $offset = 0, $count = 0, array $query = [], $prefix = '' ) {
		return Html::rawElement(
			'div',
			[
				'class' => 'smw-ui-pagination'
			],
			self::getPagingLinks( $title, $limit, $offset, $count, $query, $prefix )
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return string
	 */
	public static function filter( Title $title, $limit = 0, $offset = 0, $filter = '' ) {
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

		$label = Message::get( 'smw-filter', Message::TEXT, Message::USER_LANGUAGE );
		$content = Message::get( 'smw-property-page-filter-note', Message::PARSE, Message::USER_LANGUAGE );

		$highlighter = Highlighter::factory(
			Highlighter::TYPE_TEXT
		);

		$highlighter->setContent(
			[
				'caption' => $label,
				'content' => $content,
				'state'   => 'inline',
				'style'   => 'text-decoration: underline dotted;text-underline-offset: 3px;'
			]
		);

		$form .= Html::rawElement(
			'label',
			[],
			$highlighter->getHtml() .
			Html::rawElement(
				'input',
				[
					'type' => 'search',
					'name' => 'filter',
					'value' => $filter,
					'form' => 'search',
					'autocomplete' => 'off',
					'placeholder' => '...'
				]
			)
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-ui-input-filter'
			],
			$form
		);
	}

	/**
	 * Generate (prev x| next x) (20|50|100...) type links for paging
	 *
	 * @param Title $title Title object to link
	 * @param int $limit
	 * @param int $offset
	 * @param int $count
	 * @param array $query Optional URL query parameter string
	 * @param string $prefix
	 *
	 * @return string
	 */
	public static function getPagingLinks( Title $title, $limit, $offset, $count = 0, array $query = [], $prefix = '' ) {
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

		if ( $prefix !== '' ) {
			$prefix = Html::rawElement( 'a', [ 'class' => 'page-link link-disabled' ], $prefix );
		}

		# Make 'previous' link
		$prev = wfMessage( 'smw-prev' )->inLanguage( $language )->title( $title )->numParams( $limit )->text();

		if ( $offset > 0 ) {
			$plink = self::numLink( $title, max( $offset - $limit, 0 ), $limit, $query, $prev, 'prevn-title', 'mw-prevlink', $disabled, $language );
		} else {
			$plink = Html::element( 'a', [ 'class' => 'page-link link-disabled' ], htmlspecialchars( $prev ) );
		}

		# Make 'next' link
		$next = wfMessage( 'smw-next' )->inLanguage( $language )->title( $title )->numParams( $limit )->text();

		if ( $atend ) {
			$nlink = Html::element( 'a', [ 'class' => 'page-link link-disabled' ], htmlspecialchars( $next ) );
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
				$language,
				$num === $limit
			);
		}

		return $prefix . $plink . implode( '', $list ) . $nlink;
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
	 *
	 * @return string HTML fragment
	 */
	private static function numLink( Title $title, $offset, $limit, array $query, $link, $tooltipMsg, $class, $disabled, $language, $active = false ) {
		$query = [ 'limit' => $limit, 'offset' => $offset ] + $query;

		$tooltip = wfMessage( $tooltipMsg )->inLanguage( $language )->title( $title )->numParams( $limit )->text();
		$target = '';

		if ( isset( $query['_target'] ) ) {
			$target = $query['_target'];
			unset( $query['_target'] );
		}

		return Html::element( 'a',
			[
				'href' => $title->getLocalURL( $query ) . $target,
				'title' => $tooltip,
				'class' => 'page-link' . ( $active ? ' link-active' : '' )
			],
			$link
		);
	}

}
