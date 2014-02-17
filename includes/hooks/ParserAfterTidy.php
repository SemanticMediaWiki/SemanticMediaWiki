<?php

namespace SMW;

use Parser;
use Title;

/**
 * Hook: ParserAfterTidy to add some final processing to the
 * fully-rendered page output
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserAfterTidy extends FunctionHook {

	/** @var Parser */
	protected $parser = null;

	/** @var string */
	protected $text;

	/**
	 * @since  1.9
	 *
	 * @param Parser $parser
	 * @param string $text
	 */
	public function __construct( Parser &$parser, &$text ) {
		$this->parser = $parser;
		$this->text = $text;
	}

	/**
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
	}

	protected function canPerformUpdate() {

		if ( $this->parser->getTitle()->isSpecialPage() ) {
			return false;
		}

		if ( $this->parser->getOutput()->getProperty( 'smw-semanticdata-status' ) ||
			$this->parser->getOutput()->getCategoryLinks() ||
			$this->parser->getDefaultSort() ) {
			return true;
		}

		return false;
	}

	protected function performUpdate() {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->withContext()->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $this->parser->getTitle(),
			'ParserOutput' => $this->parser->getOutput()
		) );

		/**
		 * @var PropertyAnnotator $propertyAnnotator
		 */
		$propertyAnnotator = $this->withContext()->getDependencyBuilder()->newObject( 'CommonPropertyAnnotator', array(
			'SemanticData'  => $parserData->getData(),
			'CategoryLinks' => $this->parser->getOutput()->getCategoryLinks(),
			'DefaultSort'   => $this->parser->getDefaultSort()
		) );

		$propertyAnnotator->attach( $parserData )->addAnnotation();

		return $this->performStoreUpdateOnPurge( $parserData );
	}

	/**
	 * @note Article purge: In case an article was manually purged/moved
	 * the store is updated as well; for all other cases LinksUpdateConstructed
	 * will handle the store update
	 */
	protected function performStoreUpdateOnPurge( $parserData ) {

		/**
		 * @var CacheHandler $cache
		 */
		$cache = $this->withContext()->getDependencyBuilder()->newObject( 'CacheHandler' );

		$cache->setKey( ArticlePurge::newCacheId( $this->parser->getTitle()->getArticleID() ) );

		if( $cache->get() ) {
			$cache->delete();
			$parserData->updateStore();
		}

		return true;
	}

}
