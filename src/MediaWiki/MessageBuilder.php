<?php

namespace SMW\MediaWiki;

use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\Language\Language;
use MediaWiki\Message\Message;
use MediaWiki\Navigation\PagerNavigationBuilder;
use MediaWiki\Title\Title;
use RuntimeException;

/**
 * Convenience class to build language dependent messages and special text
 * components and decrease depdencency on the Language object with SMW's code
 * base
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class MessageBuilder {

	/**
	 * @since 2.1
	 */
	public function __construct( private ?Language $language = null ) {
	}

	/**
	 * @since 2.1
	 */
	public function setLanguage( Language $language ): static {
		$this->language = $language;
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function setLanguageFromContext( IContextSource $context ): static {
		$this->language = $context->getLanguage();
		return $this;
	}

	/**
	 * @since 2.1
	 */
	public function formatNumberToText(
		mixed $number,
		bool $useForSpecialNumbers = false
	): string {
		if ( $useForSpecialNumbers ) {
			return $this->getLanguage()->formatNumNoSeparators( $number );
		} else {
			return $this->getLanguage()->formatNum( $number );
		}
	}

	/**
	 * @since 2.1
	 */
	public function listToCommaSeparatedText( array $list ): string {
		return $this->getLanguage()->listToText( $list );
	}

	/**
	 * @since 2.1
	 */
	public function prevNextToText(
		Title $title,
		int $limit,
		int $offset,
		array $query,
		?bool $isAtTheEnd
	): string {
		$navBuilder = new PagerNavigationBuilder( RequestContext::getMain() );
		$navBuilder
			->setPage( $title )
			->setLinkQuery( [ 'limit' => $limit, 'offset' => $offset ] + $query )
			->setLimitLinkQueryParam( 'limit' )
			->setCurrentLimit( $limit )
			->setPrevTooltipMsg( 'prevn-title' )
			->setNextTooltipMsg( 'nextn-title' )
			->setLimitTooltipMsg( 'shown-title' );

		if ( $offset > 0 ) {
			$navBuilder->setPrevLinkQuery( [ 'offset' => (string)max( $offset - $limit, 0 ) ] );
		}

		if ( !$isAtTheEnd ) {
			$navBuilder->setNextLinkQuery( [ 'offset' => (string)( $offset + $limit ) ] );
		}

		return $navBuilder->getHtml();
	}

	public function cursorPrevNextToText(
		Title $title,
		int $limit,
		?int $firstCursor,
		?int $lastCursor,
		array $query,
		bool $isAtTheEnd,
		bool $isBackward = false
	): string {
		$navBuilder = new PagerNavigationBuilder( RequestContext::getMain() );
		$navBuilder
			->setPage( $title )
			->setLinkQuery( [ 'limit' => $limit ] + $query )
			->setLimitLinkQueryParam( 'limit' )
			->setCurrentLimit( $limit )
			->setPrevTooltipMsg( 'prevn-title' )
			->setNextTooltipMsg( 'nextn-title' )
			->setLimitTooltipMsg( 'shown-title' );

		// When going forward (after): atEnd means no more results ahead
		//   -> always show Previous (we navigated here), hide Next if atEnd
		// When going backward (before): atEnd means we hit the beginning
		//   -> hide Previous if atEnd, always show Next (we came from ahead)
		$showPrev = $isBackward ? !$isAtTheEnd : true;
		$showNext = $isBackward ? true : !$isAtTheEnd;

		if ( $showPrev && $firstCursor !== null ) {
			$navBuilder->setPrevLinkQuery( [
				'before' => (string)$firstCursor,
			] );
		}

		if ( $showNext && $lastCursor !== null ) {
			$navBuilder->setNextLinkQuery( [
				'after' => (string)$lastCursor,
			] );
		}

		return $navBuilder->getHtml();
	}

	/**
	 * @since 2.1
	 */
	public function getMessage( string $key ): Message {
		$params = func_get_args();
		array_shift( $params );

		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		$message = new Message( $key, $params );

		return $message->inLanguage( $this->getLanguage() )->title( $GLOBALS['wgTitle'] );
	}

	private function getLanguage(): Language {
		if ( $this->language instanceof Language ) {
			return $this->language;
		}

		throw new RuntimeException( 'Expected a valid language object' );
	}

}
