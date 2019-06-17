<?php

namespace SMW\MediaWiki\Specials\Admin;

use FormatJson;
use Html;
use OutputPage;
use SMW\Message;

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
	 *
	 * @param array $query
	 */
	public function addParentLink( $query = [], $title = 'smw-admin-tab-supplement' ) {
		$this->outputPage->prependHTML( $this->createParentLink( $query, $title ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $url
	 */
	public function addHelpLink( $url ) {
		$this->outputPage->addHelpLink( $url, true );
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
	 * @since 3.0
	 *
	 * @param string $html
	 */
	public function addAsPreformattedText( $html ) {
		$this->outputPage->addHTML( '<pre>' . $html . '</pre>' );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $css
	 */
	public function addInlineStyle( $css ) {
		$this->outputPage->addInlineStyle( $css );
	}

	/**
	 * @since 3.1
	 *
	 * @param string|array $modules
	 */
	public function addModules( $modules ) {
		$this->outputPage->addModules( $modules );
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
		if ( method_exists( $this->outputPage, 'addWikiTextAsInterface' ) ) {
			$this->outputPage->addWikiTextAsInterface( $text );
		} else {
			$this->outputPage->addWikiText( $text );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param string $fragment
	 */
	public function redirectToRootPage( $fragment = '', $query = [] ) {

		$title = \SpecialPage::getTitleFor( 'SMWAdmin' );
		$title->setFragment( ' ' . $fragment );

		$this->outputPage->redirect( $title->getFullURL( $query ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $caption
	 * @param array $query
	 */
	public function getSpecialPageLinkWith( $caption = '', $query = [] ) {
		return $this->createSpecialPageLink( $caption, $query );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $caption
	 * @param array $query
	 */
	public function createSpecialPageLink( $caption = '', $query = [] ) {
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

	private function createParentLink( $query = [], $title = 'smwadmin' ) {
		return Html::rawElement(
			'div',
			[ 'class' => 'smw-breadcrumb-link' ],
			Html::rawElement(
				'span',
				[ 'class' => 'smw-breadcrumb-arrow-right' ],
				''
			) .
			Html::rawElement(
				'a',
				[ 'href' => \SpecialPage::getTitleFor( 'SMWAdmin')->getFullURL( $query ) ],
				Message::get( $title, Message::TEXT, Message::USER_LANGUAGE )
		) );
	}

}
