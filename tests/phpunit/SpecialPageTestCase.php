<?php

namespace SMW\Test;

use DerivativeContext;
use RequestContext;
use FauxRequest;
use WebRequest;
use OutputPage;

/**
 * Class contains methods to access SpecialPages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
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
	 * This is borrowed from \Wikibase\Test\SpecialPageTestBase
	 *
	 * @param string      $sub The subpage parameter to call the page with
	 * @param \WebRequest $request Web request that may contain URL parameters, etc
	 */
	protected function execute( $sub = '', WebRequest $request = null ) {

		$request = $request === null ? new FauxRequest() : $request;
		$response = $request->response();

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setRequest( $request );

		$out = new OutputPage( $context );
		$context->setOutput( $out );

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
	protected function getOutput() {
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
