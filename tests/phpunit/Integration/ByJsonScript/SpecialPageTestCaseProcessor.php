<?php

namespace SMW\Tests\Integration\ByJsonScript;

use FauxRequest;
use Language;
use OutputPage;
use RequestContext;
use SMW\Tests\Utils\Mock\MockSuperUser;
use SpecialPage;
use SpecialPageFactory;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SpecialPageTestCaseProcessor extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	/**
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * @param Store
	 * @param StringValidator
	 */
	public function __construct( $store, $stringValidator ) {
		$this->store = $store;
		$this->stringValidator = $stringValidator;
	}

	/**
	 * @since  2.4
	 */
	public function setDebugMode( $debugMode ) {
		$this->debug = $debugMode;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $case
	 */
	public function process( array $case ) {

		if ( !isset( $case['special-page'] ) ) {
			return;
		}

		$queryParameters = isset( $case['special-page']['query-parameters'] ) ? $case['special-page']['query-parameters'] : array();

		$text = $this->getTextForRequestBy(
			SpecialPageFactory::getPage( $case['special-page']['page'] ),
			new FauxRequest( $case['special-page']['request-parameters'] ),
			$queryParameters
		);

		$this->assertOutputForCase( $case, $text );
	}

	private function getTextForRequestBy( $page, $request, $queryParameters ) {
		$response = $request->response();

		$page->setContext( $this->makeRequestContext(
			$request,
			new MockSuperUser,
			$this->getTitle( $page )
		) );

		$out = $page->getOutput();

		ob_start();
		$page->execute( $queryParameters );

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

		return $text;
	}

	private function assertOutputForCase( $case, $text ) {

		if ( isset( $case['expected-output']['to-contain'] ) ) {
			$this->stringValidator->assertThatStringContains(
				$case['expected-output']['to-contain'],
				$text,
				$case['about']
			);
		}

		if ( isset( $case['expected-output']['not-contain'] ) ) {
			$this->stringValidator->assertThatStringNotContains(
				$case['expected-output']['not-contain'],
				$text,
				$case['about']
			);
		}
	}

	/**
	 * @return RequestContext
	 */
	private function makeRequestContext( \WebRequest $request, $user, $title ) {

		$context = new RequestContext();
		$context->setRequest( $request );

		$out = new OutputPage( $context );
		$out->setTitle( $title );

		$context->setOutput( $out );
		$context->setLanguage( Language::factory( $GLOBALS['wgLanguageCode'] ) );

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
