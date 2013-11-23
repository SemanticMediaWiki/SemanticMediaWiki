<?php

namespace SMW\Test;

use FauxRequest;
use OutputPage;
use WebRequest;

/**
 * Class contains methods to access SpecialPages
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Class contains methods to access SpecialPages
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
abstract class SpecialPageTestCase extends SemanticMediaWikiTestCase {

	/**
	 * Returns a new instance of the special page under test.
	 *
	 * @return \SpecialPage
	 */
	protected abstract function getInstance();

	/**
	 * Borrowed from \Wikibase\Test\SpecialPageTestBase
	 *
	 * @param string      $sub The subpage parameter to call the page with
	 * @param \WebRequest $request Web request that may contain URL parameters, etc
	 */
	protected function execute( $sub = '', WebRequest $request = null ) {

		$request  = $request === null ? new FauxRequest() : $request;
		$response = $request->response();
		$context  = $this->newContext( $request );

		$out = new OutputPage( $context );
		$context->setOutput( $out );
		$context->setLanguage( $this->getLanguage() );

		$page = $this->getInstance();
		$page->setContext( $context );

		$out->setTitle( $page->getTitle() );

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
	 * Returns output text
	 *
	 * @return string
	 */
	protected function getText() {
		return $this->text;
	}

	/**
	 * Returns response object
	 *
	 * @return FauxResponse
	 */
	protected function getResponse() {
		return $this->response;
	}

}
