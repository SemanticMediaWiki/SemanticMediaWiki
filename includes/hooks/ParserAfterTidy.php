<?php

namespace SMW;

use Parser;
use Title;

/**
 * ParserAfterTidy hook
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Hook: ParserAfterTidy to add some final processing to the
 * fully-rendered page output
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
 *
 * @ingroup Hook
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
	 * @note Article purge: In case an article was manually purged/moved
	 * the store is updated as well and for all other cases LinksUpdateConstructed
	 * will handle the store update
	 *
	 * @note Store update: For NS_FILE ParserAfterTidy is initiated several
	 * times and somewhere in-between an empty ParserOuput object is
	 * returned which would cause the existing output properties being overridden
	 * therefore purge refresh for NS_FILE is not supported
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	protected function performUpdate( Title $title ) {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $title,
			'ParserOutput' => $this->parser->getOutput()
		) );

		/**
		 * @var BasePropertyAnnotator $propertyAnnotator
		 */
		$propertyAnnotator = $this->getDependencyBuilder()->newObject( 'BasePropertyAnnotator', array(
			'SemanticData' => $parserData->getData(),
		) );

		$propertyAnnotator->attach( $parserData )
			->addCategories( $this->parser->getOutput()->getCategoryLinks() )
			->addDefaultSort( $this->parser->getDefaultSort() );

		/**
		 * @var CacheHandler $cache
		 */
		$cache = $this->getDependencyBuilder()->newObject( 'CacheHandler' );

		$cache->setKey( ArticlePurge::newIdGenerator( $title->getArticleID() ) );

		if( $cache->get() && !$title->inNamespace( NS_FILE ) ) {
			$cache->delete();
			$parserData->updateStore();
		}

		return true;
	}

	/**
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return !$this->parser->getTitle()->isSpecialPage() ? $this->performUpdate( $this->parser->getTitle() ) : true;
	}

}
