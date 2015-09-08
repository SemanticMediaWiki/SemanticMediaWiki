<?php

namespace SMW;

use Onoi\CallbackContainer\CallbackContainer;
use Onoi\CallbackContainer\CallbackLoader;

use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\TitleCreator;
use SMW\MediaWiki\Jobs\JobFactory;
use SMW\Factbox\FactboxFactory;
use Closure;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SharedCallbackContainer implements CallbackContainer {

	public function register( CallbackLoader $callbackLoader ) {
		$this->registerCallbackHandlers( $callbackLoader );
	}

	private function registerCallbackHandlers( $callbackLoader ) {

		$callbackLoader->registerExpectedReturnType( 'Settings', '\SMW\Settings' );

		$callbackLoader->registerCallback( 'Settings', function() use ( $callbackLoader )  {
			return Settings::newFromGlobals();
		} );

		$callbackLoader->registerExpectedReturnType( 'Store', '\SMW\Store' );

		$callbackLoader->registerCallback( 'Store', function() use ( $callbackLoader ) {
			return StoreFactory::getStore( $callbackLoader->singleton( 'Settings' )->get( 'smwgDefaultStore' ) );
		} );

		$callbackLoader->registerExpectedReturnType( 'Cache', '\Onoi\Cache\Cache' );

		$callbackLoader->registerCallback( 'Cache', function() {
			return ApplicationFactory::getInstance()->newCacheFactory()->newMediaWikiCompositeCache();
		} );

		$callbackLoader->registerCallback( 'NamespaceExaminer', function() use ( $callbackLoader ) {
			return NamespaceExaminer::newFromArray( $callbackLoader->singleton( 'Settings' )->get( 'smwgNamespacesWithSemanticLinks' ) );
		} );

		$callbackLoader->registerExpectedReturnType( 'ParserData', '\SMW\ParserData' );

		$callbackLoader->registerCallback( 'ParserData', function( \Title $title, \ParserOutput $parserOutput ) {
			return new ParserData( $title, $parserOutput );
		} );

		$callbackLoader->registerCallback( 'MessageFormatter', function( \Language $language ) {
			return new MessageFormatter( $language );
		} );

		$callbackLoader->registerExpectedReturnType( 'PageCreator', '\SMW\MediaWiki\PageCreator' );

		$callbackLoader->registerCallback( 'PageCreator', function() {
			return new PageCreator();
		} );

		$callbackLoader->registerExpectedReturnType( 'TitleCreator', '\SMW\MediaWiki\TitleCreator' );

		$callbackLoader->registerCallback( 'TitleCreator', function() {
			return new TitleCreator();
		} );

		$callbackLoader->registerExpectedReturnType( 'WikiPage', '\WikiPage' );

		$callbackLoader->registerCallback( 'WikiPage', function( \Title $title ) {
			return \WikiPage::factory( $title );
		} );

		$callbackLoader->registerExpectedReturnType( 'ContentParser', '\SMW\ContentParser' );

		$callbackLoader->registerCallback( 'ContentParser', function( \Title $title ) {
			return new ContentParser( $title );
		} );

		$callbackLoader->registerExpectedReturnType( 'JobFactory', '\SMW\MediaWiki\Jobs\JobFactory' );

		$callbackLoader->registerCallback( 'JobFactory', function() {
			return new JobFactory();
		} );

		$callbackLoader->registerExpectedReturnType( 'FactboxFactory', '\SMW\Factbox\FactboxFactory' );

		$callbackLoader->registerCallback( 'FactboxFactory', function() {
			return new FactboxFactory();
		} );
	}

}
