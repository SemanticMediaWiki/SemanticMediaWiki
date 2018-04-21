<?php

namespace SMW\Tests\Utils\Validators;

use DOMDocument;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author Stephan Gambke
 */
class HtmlValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @var array
	 */
	private $documentCache = [];

	/**
	 * @var null|boolean
	 */
	private $canUse;

	/**
	 * @param string $actual
	 * @param string $message
	 */
	public function assertThatHtmlIsValid( $actual, $message = '' ) {

		$document = $this->getDomDocumentFromHtmlFragment( $actual );

		self::assertTrue( $document !== false, "Failed test `{$message}` (assertion HtmlIsValid) for $actual" );
	}

	/**
	 * @return boolean
	 */
	public function canUse() {

		if ( $this->canUse === null ) {
			$this->canUse = class_exists( '\Symfony\Component\CssSelector\CssSelectorConverter' );
		}

		return $this->canUse;
	}

	/**
	 * @param string $fragment
	 *
	 * @return bool|DOMDocument
	 */
	private function getDomDocumentFromHtmlFragment( $fragment ) {

		$cacheKey = md5( $fragment );

		if ( !isset( $this->documentCache[ $cacheKey ] ) ) {
			$this->addHtmlFragmentToCache( $fragment, $cacheKey );
		}

		return $this->documentCache[ $cacheKey ];
	}

	/**
	 * @param $fragment
	 * @param $cacheKey
	 */
	private function addHtmlFragmentToCache( $fragment, $cacheKey ) {

		$fragment = self::wrapHtmlFragment( $fragment );

		$document = new DOMDocument();
		$document->preserveWhiteSpace = false;

		libxml_use_internal_errors( true );
		$result = $document->loadHTML( $fragment );
		libxml_use_internal_errors( false );

		$this->documentCache[ $cacheKey ] = ( $result === true ) ? $document : false;

	}

	/**
	 * @param string $fragment
	 *
	 * @return string
	 */
	private static function wrapHtmlFragment( $fragment ) {
		return "<!DOCTYPE html><html><head><meta charset='utf-8'/><title>SomeTitle</title></head><body>$fragment</body></html>";
	}

	/**
	 * @param string | string[] $cssSelectors
	 * @param string $htmlFragment
	 * @param string $message
	 */
	public function assertThatHtmlContains( $cssSelectors, $htmlFragment, $message = '', $expected = true ) {

		$document = $this->getDomDocumentFromHtmlFragment( $htmlFragment );
		$xpath = new \DOMXPath( $document );
		$converter = new CssSelectorConverter();

		foreach ( $cssSelectors as $selector ) {

			if ( is_array( $selector ) ) {
				$expectedCount = array_pop( $selector );
				$expectedCountText = ( ( $expected === true) ? '' : 'not ') . $expectedCount;
				$selector = array_shift( $selector );
			} else {
				$expectedCount = false;
				$expectedCountText = ( $expected === true) ? 'at least 1' : 'none';
			}

			$message = "Failed assertion for test case `{$message}` on: \n=====\n$htmlFragment\n=====\nExpected pattern: `$selector`\n";

			try {
				// Symfony\Component\CssSelector\Exception\SyntaxErrorException: Expected selector ...
				$entries = $xpath->evaluate( $converter->toXPath( $selector ) );
				$actualCount = $entries->length;

				$message .= "Expected occurrences: {$expectedCountText}\nFound occurrences: {$actualCount}\n";

			} catch ( \Exception $e ) {
				$actualCount = 0;
				$message .= "CssSelector: " . $e->getMessage();
			}

			self::assertTrue( ( ( $expectedCount === false && $actualCount > 0 ) || ( $actualCount === $expectedCount ) ) === $expected, $message );
		}
	}

	/**
	 * @param string | string[] $cssSelectors
	 * @param string $htmlFragment
	 * @param string $message
	 */
	public function assertThatHtmlNotContains( $cssSelectors, $htmlFragment, $message = '' ) {
		$this->assertThatHtmlContains( $cssSelectors, $htmlFragment, $message, false );
	}
}
