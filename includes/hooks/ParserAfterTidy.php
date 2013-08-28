<?php

namespace SMW;

use Parser;

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
	 * @see FunctionHook::process
	 *
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
	public function process() {

		$title = $this->parser->getTitle();

		if ( $title->isSpecialPage() ) {
			return true;
		}

		/**
		 * @var Settings $settings
		 */
		$settings = $this->getDependencyBuilder()->newObject( 'Settings' );

		/**
		 * @var CacheHandler $cache
		 */
		$cache = $this->getDependencyBuilder()->newObject( 'CacheHandler' );

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $title,
			'ParserOutput' => $this->parser->getOutput()
		) );

		$annotator = new BasePropertyAnnotator( $parserData->getData(), $settings );
		$annotator->attach( $parserData );

		$annotator->addCategories( $this->parser->getOutput()->getCategoryLinks() );
		$annotator->addDefaultSort( $this->parser->getDefaultSort() );

		$cache->setKey( ArticlePurge::newIdGenerator( $title->getArticleID() ) );

		if( $cache->get() && !$title->inNamespace( NS_FILE ) ) {
			$cache->delete();
			$parserData->updateStore();
		}

		return true;
	}

}
