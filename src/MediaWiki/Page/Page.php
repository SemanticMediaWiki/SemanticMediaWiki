<?php

namespace SMW\MediaWiki\Page;

use Article;
use SMW\DIWikiPage;
use SMW\Options;
use SMWOutputs as Outputs;

/**
 * Abstract subclass of MediaWiki's Article that handles the common tasks of
 * article pages for Concept and Property pages. This is mainly parameter
 * handling and some very basic output control.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
abstract class Page extends Article {

	/**
	 * Limit for results per page.
	 *
	 * @var integer
	 */
	protected $limit;

	/**
	 * Start string: print $limit results from here.
	 *
	 * @var string
	 */
	protected $from;

	/**
	 * End string: print $limit results strictly before this article.
	 *
	 * @var string
	 */
	protected $until;

	/**
	 * Cache for the current skin, obtained from $wgUser.
	 *
	 * @var Skin
	 */
	protected $skin;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * Overwrite Article::view to add additional HTML to the output.
	 *
	 * @see Article::view
	 */
	public function view() {

		$outputPage = $this->getContext()->getOutput();
		$outputPage->addModuleStyles( 'ext.smw.page.styles' );

		if ( !$this->getOption( 'smwgSemanticsEnabled' ) ) {
			$outputPage->setPageTitle( $this->getTitle()->getPrefixedText() );
			$outputPage->addHTML( wfMessage( 'smw-semantics-not-enabled' )->text() );
			return;
		}

		if ( ( $redirectTargetURL = $this->getRedirectTargetURL() ) !== false ) {
			$outputPage->redirect( $redirectTargetURL );
		}

		$this->initParameters();

		// Copied from CategoryPage
		$user = $this->getContext()->getUser();
		$request = $this->getContext()->getRequest();

		$diff = $request->getVal( 'diff' );
		$diffOnly = $request->getBool( 'diffonly', $user->getOption( 'diffonly' ) );

		if ( !isset( $diff ) || !$diffOnly ) {
			// MW 1.25+
			if ( method_exists( $outputPage, 'setIndicators' ) && ( $indicators = $this->getTopIndicators() ) !== '' ) {
				$outputPage->setIndicators( $indicators );
			}

			$outputPage->addHTML( $this->initHtml() );
			$outputPage->addHTML( $this->beforeView() );
		}

		if ( $this->isLockedView() === false ) {
			parent::view();
		}

		if ( !isset( $diff ) || !$diffOnly ) {
			$this->showList();
		}

		$outputPage->addHTML( $this->afterHtml() );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOption( $key ) {

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		return $this->options->safeGet( $key, false );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		return $this->options->set( $key, $value );
	}

	/**
	 * @since 3.0
	 *
	 * @return string|boolean
	 */
	protected function getRedirectTargetURL() {
		return false;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	protected function getTopIndicators() {
		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	protected function initHtml() {
		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	protected function isLockedView() {
		return false;
	}

	/**
	 * Main method for adding all additional HTML to the output stream.
	 */
	protected function showList() {

		$outputPage = $this->getContext()->getOutput();
		$request = $this->getContext()->getRequest();

		$this->from = $request->getVal( 'from', '' );
		$this->until = $request->getVal( 'until', '' );

		$outputPage->addHTML( $this->getHtml() );

		Outputs::commitToOutputPage( $outputPage );
	}

	/**
	 * Initialise some parameters that might be changed by subclasses
	 * (e.g. $limit). Method can be overwritten in this case.
	 * If the method returns false, nothing will be printed besides
	 * the original article.
	 *
	 * @return true
	 */
	protected function initParameters() {
		$this->limit = 20;
	}

	/**
	 * Returns HTML to be displayed before the article text.
	 *
	 * @return string
	 */
	protected function beforeView() {
		return '';
	}

	/**
	 * Returns HTML to be displayed after the list display.
	 *
	 * @return string
	 */
	protected function afterHtml() {
		return '';
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 *
	 * @return string
	 */
	protected abstract function getHtml();

	/**
	 * Like Article's getTitle(), but returning a suitable SMWDIWikiPage.
	 *
	 * @since 1.6
	 *
	 * @return SMWDIWikiPage
	 */
	protected function getDataItem() {
		return DIWikiPage::newFromTitle( $this->getTitle() );
	}

}
