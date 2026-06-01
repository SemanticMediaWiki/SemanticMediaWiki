<?php

namespace SMW\Localizer;

use InvalidArgumentException;
use MediaWiki\Html\Html;
use MediaWiki\Html\HtmlHelper;
use MediaWiki\Language\Language;
use Wikimedia\RemexHtml\Serializer\SerializerNode;

/**
 * Builds and resolves the post-cache "deferred localized text" marker used to keep
 * `#ask` presentation-format output language-neutral in the parser cache.
 *
 * A marker is a self-contained <span> carrying an allow-listed id in `data-smw-msg`
 * and the content-language text as a graceful fallback. It is resolved per-request,
 * after the parser cache, to the viewer's interface language.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class DeferredLocalizedMessage {

	public const CLASS_NAME = 'smw-localized-message';

	/**
	 * Allow-list of marker id => message key. Only these ids are resolved, so a
	 * marker hand-written in wikitext can never surface an arbitrary interface message.
	 */
	private const ALLOWED = [
		'further-results' => 'smw_iq_moreresults',
		'category-continues' => 'smw-listingcontinuesabbrev',
	];

	/**
	 * @since 7.0.0
	 */
	public static function newMarker( string $id ): string {
		if ( !isset( self::ALLOWED[$id] ) ) {
			throw new InvalidArgumentException( "Unknown deferred message id: $id" );
		}

		return Html::element(
			'span',
			[ 'class' => self::CLASS_NAME, 'data-smw-msg' => $id ],
			Message::get( self::ALLOWED[$id], Message::TEXT, Message::CONTENT_LANGUAGE )
		);
	}

	/**
	 * @since 7.0.0
	 */
	public static function resolve( string $html, Language $language ): string {
		if ( strpos( $html, self::CLASS_NAME ) === false ) {
			return $html;
		}

		return HtmlHelper::modifyElements(
			$html,
			static function ( SerializerNode $node ): bool {
				return $node->name === 'span'
					&& ( $node->attrs['class'] ?? '' ) === self::CLASS_NAME
					&& isset( self::ALLOWED[$node->attrs['data-smw-msg'] ?? ''] );
			},
			static function ( SerializerNode $node ) use ( $language ) {
				$key = self::ALLOWED[$node->attrs['data-smw-msg']];
				// HtmlHelper splices the returned string in as raw outer HTML, and the
				// message is plain text, so it must be escaped here to avoid markup injection.
				return htmlspecialchars(
					wfMessage( $key )->inLanguage( $language )->text()
				);
			},
			false
		);
	}

}
