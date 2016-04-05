<?php

namespace SMW\Test;

use FauxRequest;
use Language;
use OutputPage;
use RequestContext;
use SMW\Tests\Utils\Mock\MockSuperUser;
use SpecialPage;
use WebRequest;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
abstract class SpecialPageTestCase extends \PHPUnit_Framework_TestCase {

	protected $obLevel;
	protected $store = null;

	protected function setUp() {
		parent::setUp();
		$this->obLevel = ob_get_level();
	}

	protected function tearDown() {

		$obLevel = ob_get_level();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}

		parent::tearDown();
	}

	/**
	 * @return SpecialPage
	 */
	protected abstract function getInstance();

	protected function setStore( $store ) {
		$this->store = $store;
	}

	/**
	 * Borrowed from \Wikibase\Test\SpecialPageTestBase
	 *
	 * @param string      $sub The subpage parameter to call the page with
	 * @param WebRequest $request Web request that may contain URL parameters, etc
	 */
	protected function execute( $sub = '', WebRequest $request = null, $user = null ) {

		$request  = $request === null ? new FauxRequest() : $request;
		$response = $request->response();

		$page = $this->getInstance();

		if ( $this->store !== null ) {
			$page->setStore( $this->store );
		}

		$page->setContext( $this->makeRequestContext(
			$request,
			$user,
			$this->getTitle( $page )
		) );

		$out = $page->getOutput();

		ob_start();
		$page->execute( $sub );

		if ( $out->getRedirect() !== '' ) {
			$out->output();
			$text = ob_get_contents();
		} elseif ( $out->isDisabled() ) {
			$text = ob_get_contents();
		} else {
			$text = $out->getHTML();
		}

		ob_end_clean();

		$code = $response->getStatusCode();

		if ( $code > 0 ) {
			$response->header( "Status: " . $code . ' ' . \HttpStatus::getMessage( $code ) );
		}

		$this->text = $text;
		$this->response = $response;
	}

	/**
	 * @return string
	 */
	protected function getText() {
		return $this->text;
	}

	/**
	 * @return FauxResponse
	 */
	protected function getResponse() {
		return $this->response;
	}

	/**
	 * @return RequestContext
	 */
	private function makeRequestContext( WebRequest $request, $user, $title ) {

		$context = new RequestContext();
		$context->setRequest( $request );

		$out = new OutputPage( $context );
		$out->setTitle( $title );

		$context->setOutput( $out );
		$context->setLanguage( Language::factory( 'en' ) );

		$user = $user === null ? new MockSuperUser() : $user;
		$context->setUser( $user );

		return $context;
	}

	/**
	 * Deprecated: Use of SpecialPage::getTitle was deprecated in MediaWiki 1.23
	 *
	 * @return Title
	 */
	private function getTitle( SpecialPage $page ) {
		return method_exists( $page, 'getPageTitle') ? $page->getPageTitle() : $page->getTitle();
	}

}
