<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\ParserOutputPostCacheTransformHook;
use SMW\Localizer\DeferredLocalizedMessage;

/**
 * Resolves SMW deferred-localized-text markers after the parser cache, per request.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserOutputPostCacheTransform
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ParserOutputPostCacheTransform implements ParserOutputPostCacheTransformHook {

	/**
	 * @inheritDoc
	 */
	public function onParserOutputPostCacheTransform( $parserOutput, &$text, &$options ): void {
		if ( strpos( $text, DeferredLocalizedMessage::CLASS_NAME ) === false ) {
			return;
		}

		$language = $options['userLang']
			?? ( isset( $options['skin'] ) ? $options['skin']->getLanguage() : null )
			?? RequestContext::getMain()->getLanguage();

		$text = DeferredLocalizedMessage::resolve( $text, $language );
	}

}
