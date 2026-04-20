<?php

namespace SMW\MediaWiki\Page;

use Article;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Outputs;
use SMW\Options;
use SMW\Services\ServicesFactory;

/**
 * Abstract subclass of MediaWiki's Article that handles the common tasks of
 * article pages for Concept and Property pages. This is mainly parameter
 * handling and some very basic output control.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
abstract class Page extends Article {

	/**
	 * Limit for results per page.
	 */
	protected int $limit;

	/**
	 * Start string: print $limit results from here.
	 */
	protected string $from;

	/**
	 * End string: print $limit results strictly before this article.
	 */
	protected string $until;

	private ?Options $options = null;

	/**
	 * Overwrite Article::view to add additional HTML to the output.
	 *
	 * @see Article::view
	 */
	public function view(): void {
		$outputPage = $this->getContext()->getOutput();
		$outputPage->addModuleStyles( [
			'ext.smw.styles',
			'ext.smw.page.styles'
		] );

		if ( !$this->getOption( 'SMW_EXTENSION_LOADED' ) ) {
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
		$userOptionsLookup = ServicesFactory::getInstance()->singleton( 'UserOptionsLookup' );
		$diffOnly = $request->getBool( 'diffonly', $userOptionsLookup->getOption( $user, 'diffonly' ) );

		if ( $diff === null || !$diffOnly ) {
			$outputPage->addHTML( $this->initHtml() );
			$outputPage->addHTML( $this->beforeView() );
		}

		if ( !$this->isLockedView() ) {
			parent::view();
		}

		if ( $diff === null || !$diffOnly ) {
			$this->showList();
		}

		$outputPage->addHTML( $this->afterHtml() );
	}

	/**
	 * @since 3.0
	 */
	public function getOption( string $key ): mixed {
		if ( $this->options === null ) {
			$this->options = new Options();
		}

		return $this->options->safeGet( $key, false );
	}

	/**
	 * @since 3.0
	 */
	public function setOption( string $key, mixed $value ): void {
		if ( $this->options === null ) {
			$this->options = new Options();
		}

		$this->options->set( $key, $value );
	}

	/**
	 * @since 3.0
	 */
	protected function getRedirectTargetURL(): string|bool {
		return false;
	}

	/**
	 * @since 3.0
	 */
	protected function initHtml(): string {
		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
	 */
	protected function isLockedView(): bool {
		return false;
	}

	/**
	 * Main method for adding all additional HTML to the output stream.
	 */
	protected function showList(): void {
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
	 */
	protected function initParameters(): void {
		$this->limit = 20;
	}

	/**
	 * Returns HTML to be displayed before the article text.
	 */
	protected function beforeView(): string {
		return '';
	}

	/**
	 * Returns HTML to be displayed after the list display.
	 */
	protected function afterHtml(): string {
		return '';
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 */
	abstract protected function getHtml();

	/**
	 * Like Article's getTitle(), but returning a suitable SMWDIWikiPage.
	 *
	 * @since 1.6
	 */
	protected function getDataItem(): WikiPage {
		return WikiPage::newFromTitle( $this->getTitle() );
	}

}
