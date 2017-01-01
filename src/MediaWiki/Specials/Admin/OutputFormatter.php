<?php

namespace SMW\MediaWiki\Specials\Admin;

use OutputPage;
use FormatJson;
use SMW\Message;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class OutputFormatter {

	/**
	 * @var OutputPage
	 */
	private $outputPage;

	/**
	 * @since 2.5
	 *
	 * @param OutputPage $outputPage
	 */
	public function __construct( OutputPage $outputPage ) {
		$this->outputPage = $outputPage;
	}

	/**
	 * @since 2.5
	 */
	public function addParentLink() {
		$this->outputPage->prependHTML( $this->createParentLink() );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $title
	 */
	public function setPageTitle( $title ) {
		$this->outputPage->setArticleRelated( false );
		$this->outputPage->setPageTitle( $title );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $html
	 */
	public function addHTML( $html ) {
		$this->outputPage->addHTML( $html );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 */
	public function addWikiText( $text ) {
		$this->outputPage->addWikiText( $text );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $fragment
	 */
	public function redirectToRootPage( $fragment = '' ) {

		$title = \SpecialPage::getTitleFor( 'SMWAdmin' );
		$title->setFragment( ' ' . $fragment );

		$this->outputPage->redirect( $title->getFullURL() );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $caption
	 * @param array $query
	 */
	public function getSpecialPageLinkWith( $caption = '', $query = array() ) {
		return '<a href="' . htmlspecialchars( \SpecialPage::getTitleFor( 'SMWAdmin' )->getFullURL( $query ) ) . '">' . $caption . '</a>';
	}

	/**
	 * @since 2.5
	 *
	 * @param callable $text
	 */
	public function formatAsRaw( callable $text ) {
		$this->outputPage->disable(); // raw output
		ob_start();

		// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
		print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Semantic MediaWiki</title></head><body><p><pre>";
		// @codingStandardsIgnoreEnd
		// header( "Content-type: text/html; charset=UTF-8" );
		$text( $this );
		print '</pre></p>';
		// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
		print '<b> ' . $this->getSpecialPageReturnLink() . "</b>\n";
		// @codingStandardsIgnoreEnd
		print '</body></html>';

		ob_flush();
		flush();
	}

	/**
	 *@note JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, and
	 * JSON_UNESCAPED_UNICOD were only added with 5.4
	 *
	 * @since 2.5
	 *
	 * @param array $input
	 *
	 * @return string
	 */
	public function encodeAsJson( array $input ) {

		if ( defined( 'JSON_PRETTY_PRINT' ) ) {
			return json_encode( $input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		return FormatJson::encode( $input, true );
	}

	private function createParentLink() {
		return Html::rawElement(
			'div',
			array( 'class' => 'smw-breadcrumb-link' ),
			Html::rawElement(
				'span',
				array( 'class' => 'smw-breadcrumb-arrow-right' ),
				''
			) .
			Html::rawElement(
				'a',
				array( 'href' => \SpecialPage::getTitleFor( 'SMWAdmin')->getFullURL() ),
				Message::get( 'smwadmin', Message::TEXT, Message::USER_LANGUAGE )
		) );
	}

}
