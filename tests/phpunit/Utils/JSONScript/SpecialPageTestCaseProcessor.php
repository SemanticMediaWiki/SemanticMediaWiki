<?php

namespace SMW\Tests\Utils\JSONScript;

use FauxRequest;
use Language;
use OutputPage;
use RequestContext;
use SMW\Tests\Utils\File\ContentsReader;
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
	 * @var string
	 */
	private $testCaseLocation = '';

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
	 * @since 3.0
	 *
	 * @param string $testCaseLocation
	 */
	public function setTestCaseLocation( $testCaseLocation ) {
		$this->testCaseLocation = $testCaseLocation;
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

		if ( isset( $case['special-page']['query-parameters'] ) ) {
			$queryParameters = $case['special-page']['query-parameters'];
		} else {
			$queryParameters = [];
		}

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

		if ( $this->debug ) {
			var_dump(
				"\n\n== DEBUG (start) ==\n\n" . $text .
				"\n\n== DEBUG (end) ==\n\n"
			);
		}

		$code = $response->getStatusCode();

		if ( $code > 0 ) {
			$response->header( "Status: " . $code . ' ' . \HttpStatus::getMessage( $code ) );
		}

		return $text;
	}

	private function assertOutputForCase( $case, $text ) {

		// Avoid issue with \r carriage return and \n new line
		$text = str_replace( "\r\n", "\n", $text );

		if ( isset( $case['assert-output']['to-contain'] ) ) {

			if ( isset( $case['assert-output']['to-contain']['contents-file'] ) ) {
				$contents = ContentsReader::readContentsFrom(
					$this->testCaseLocation . $case['assert-output']['to-contain']['contents-file']
				);
			} else {
				$contents = $case['assert-output']['to-contain'];
			}

			$this->stringValidator->assertThatStringContains(
				$contents,
				$text,
				$case['about']
			);
		}

		if ( isset( $case['assert-output']['not-contain'] ) ) {

			if ( isset( $case['assert-output']['not-contain']['contents-file'] ) ) {
				$contents = ContentsReader::readContentsFrom(
					$this->testCaseLocation . $case['assert-output']['not-contain']['contents-file']
				);
			} else {
				$contents = $case['assert-output']['not-contain'];
			}

			$this->stringValidator->assertThatStringNotContains(
				$contents,
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
