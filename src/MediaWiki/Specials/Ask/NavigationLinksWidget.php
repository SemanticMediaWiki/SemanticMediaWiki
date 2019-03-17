<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Localizer;
use SMW\Message;
use SMW\Utils\HtmlModal;
use SMW\Utils\Pager;
use SMWInfolink as Infolink;
use Title;
use SMW\Utils\HtmlTabs;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class NavigationLinksWidget {

	/**
	 * @var integer
	 */
	private static $maxInlineLimit = 500;

	/**
	 * @since 3.0
	 *
	 * @param string $maxInlineLimit
	 */
	public static function setMaxInlineLimit( $maxInlineLimit ) {
		self::$maxInlineLimit = $maxInlineLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title,
	 * @param array $visibleLinks
	 *
	 * @return string
	 */
	public static function topLinks( Title $title, $visibleLinks = [], $isEditMode = true ) {

		if ( $visibleLinks === [] ) {
			return '';
		}

		$lLinks = [];
		$rLinks = [];

		$lLinks['options'] = Html::rawElement(
			'a',
			[
				'href' => '#options'
			],
			Message::get( 'smw-ask-options', Message::TEXT, Message::USER_LANGUAGE )
		);

		$lLinks['search'] = Html::rawElement(
			'a',
			[
				'href' => '#search'
			],
			Message::get( 'smw-ask-search', Message::TEXT, Message::USER_LANGUAGE )
		);

		$lLinks['result'] = Html::rawElement(
			'a',
			[
				'href' => '#result'
			],
			Message::get( 'smw-ask-result', Message::TEXT, Message::USER_LANGUAGE )
		);

		$rLinks['empty'] = Html::rawElement(
			'a',
			[
				'href' => $title->getLocalURL()
			],
			Message::get( 'smw-ask-empty', Message::TEXT, Message::USER_LANGUAGE )
		);

		$rLinks['help'] = HtmlModal::link(
			'<span class="smw-icon-info" style="padding: 0 0 3px 18px;background-position-x: center;"></span>',
			[
				'data-id' => 'ask-help'
			]
		);

		$visibleLinks = array_flip( $visibleLinks );

		foreach ( $lLinks as $key => $value ) {
			if ( !isset( $visibleLinks[$key] ) ) {
				unset( $lLinks[$key] );
			}
		}

		foreach ( $rLinks as $key => $value ) {
			if ( !isset( $visibleLinks[$key] ) ) {
				unset( $rLinks[$key] );
			}
		}

		$sep = Html::rawElement(
			'span',
			[
				'style' => 'color:#aaa;font-size: 95%;margin-top: 2px;'
			],
			'&#160;&#160;|&#160;&#160;'
		);

		$left = Html::rawElement(
			'span',
			[
				'class' => 'float-left'
			],
			implode( "$sep", $lLinks )
		);

		$right = Html::rawElement(
			'span',
			[
				'class' => 'float-right'
			],
			implode( "$sep", $rLinks )
		);

		$html = Html::rawElement(
			'div',
			[
				'id' => 'ask-toplinks',
				'class' => 'smw-ask-toplinks' . ( !$isEditMode ? ' hide-mode' : '' )
			],
			$left . '&#160;' . $right
		) . Html::rawElement(
			'div',
			[
				'class' => 'clear-both'
			]
		);

		return $html;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title,
	 * @param UrlArgs $urlArgs
	 * @param integer $count
	 * @param boolean $hasFurtherResults
	 *
	 * @return string
	 */
	public static function navigationLinks( Title $title, UrlArgs $urlArgs, $count, $hasFurtherResults = false ) {

		if ( $count == 0 ) {
			return '';
		}

		$urlArgs = clone $urlArgs;
		$limit = $urlArgs->get( 'limit' );
		$offset = $urlArgs->get( 'offset' );

		// Remove any contents that is cruft
		if ( strpos( $urlArgs->get( 'p' ), 'cl=' ) !== false ) {
			$urlArgs->set( 'p', mb_substr( $urlArgs->get( 'p' ), stripos( $urlArgs->get( 'p' ), '/' ) + 1 ) );
		}

		$userLanguage = Localizer::getInstance()->getUserLanguage();

		$html =	'<b>' .
				Message::get( 'smw_result_results', Message::TEXT, Message::USER_LANGUAGE ) . ' ' . $userLanguage->formatNum( $offset + 1 ) .
			' &#150; ' .
				$userLanguage->formatNum( $offset + $count ) .
			'</b>&#160;';

		return Html::rawElement(
			'div',
			[
				'id' => 'ask-pagination'
			],
			Pager::pagination( $title, $limit, $offset, $count, $urlArgs->toArray() + [ '_target' => '#search' ] , $html )
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param string $navigation
	 * @param string $infoText
	 * @param Infolink|null $infoLink
	 * @param string $editHref
	 *
	 * @return string
	 */
	public static function basicLinks( $navigation = '', Infolink $infoLink = null ) {

		if ( $navigation === '' ) {
			return '';
		}

		$downloadLink = DownloadLinksWidget::downloadLinks(
			$infoLink
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-ask-actions-nav'
			],
			$navigation . $downloadLink
		);
	}

}
