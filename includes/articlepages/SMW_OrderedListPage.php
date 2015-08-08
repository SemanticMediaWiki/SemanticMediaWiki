<?php

use SMW\PropertyRegistry;
use SMW\DIProperty;

/**
 * Abstract subclass of MediaWiki's Article that handles the common tasks of
 * article pages for Concept and Property pages. This is mainly parameter
 * handling and some very basic output control.
 *
 * @ingroup SMW
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
abstract class SMWOrderedListPage extends Article {

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
	 * Property that the displayed values are for, if any.
	 *
	 * @since 1.6
	 *
	 * @var SMWDIProperty
	 */
	protected $mProperty = null;

	/**
	 * Overwrite view() from Article.php to add additional HTML to the
	 * output.
	 */
	public function view() {
		global $wgRequest, $wgUser;

		if ( $this->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->findBasePropertyToRedirectFor( $this->getTitle()->getText() );
		}

		parent::view();

		// Copied from CategoryPage
		$diff = $wgRequest->getVal( 'diff' );
		$diffOnly = $wgRequest->getBool( 'diffonly', $wgUser->getOption( 'diffonly' ) );
		if ( !isset( $diff ) || !$diffOnly ) {
			$this->showList();
		}
	}

	private function findBasePropertyToRedirectFor( $label ) {

		$property = new DIProperty(
			PropertyRegistry::getInstance()->findPropertyIdByLabel( $label )
		);

		if ( $property->getLabel() !== '' && $label !== $property->getLabel() ) {
			$outputPage = $this->getContext()->getOutput();
			$outputPage->redirect( $property->getDiWikiPage()->getTitle()->getFullURL() );
		}
	}

	/**
	 * Main method for adding all additional HTML to the output stream.
	 */
	protected function showList() {
		global $wgOut, $wgRequest;


		$this->from = $wgRequest->getVal( 'from', '' );
		$this->until = $wgRequest->getVal( 'until', '' );

		if ( $this->initParameters() ) {
			$wgOut->addHTML( $this->getHtml() );
			SMWOutputs::commitToOutputPage( $wgOut );
		}

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
		return true;
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
		return SMWDIWikiPage::newFromTitle( $this->getTitle() );
	}

}
