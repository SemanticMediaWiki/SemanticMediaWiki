<?php

namespace SMW\MediaWiki\Content;

use Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\Transform\PreSaveTransformParams;
use JsonContentHandler;
use Title;
use ParserOutput;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentHandler extends JsonContentHandler {

	public function __construct() {
		parent::__construct( CONTENT_MODEL_SMW_SCHEMA, [ CONTENT_FORMAT_JSON ] );
	}

	/**
	 * Returns true, because wikitext supports caching using the
	 * ParserCache mechanism.
	 *
	 * @since 1.21
	 *
	 * @return bool Always true.
	 *
	 * @see ContentHandler::isParserCacheSupported
	 */
	public function isParserCacheSupported() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	protected function getContentClass() {
		return SchemaContent::class;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsSections() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsCategories() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsRedirects() {
		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 */
	public function preSaveTransform( Content $content, PreSaveTransformParams $pstParams ): Content {
		return $content->preSaveTransform(
			$pstParams->getPage(),
			$pstParams->getUser(),
			$pstParams->getParserOptions()
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	) {
		$title = Title::castFromPageReference( $cpoParams->getPage() );
		$content->fillParserOutput(
			$title,
			$cpoParams->getRevId(),
			$cpoParams->getParserOptions(),
			$cpoParams->getGenerateHtml(),
			$output
		);
	}
}
