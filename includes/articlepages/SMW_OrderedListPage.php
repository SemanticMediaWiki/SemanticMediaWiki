<?php

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\PropertyRegistry;

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
	 * @var boolean
	 */
	protected $isLockedView = false;

	/**
	 * Overwrite view() from Article.php to add additional HTML to the
	 * output.
	 */
	public function view() {
		global $wgUser;

		$outputPage = $this->getContext()->getOutput();
		$request = $this->getContext()->getRequest();

		if ( !ApplicationFactory::getInstance()->getSettings()->get( 'smwgSemanticsEnabled' ) ) {
			$outputPage->setPageTitle( $this->getTitle()->getPrefixedText() );
			$outputPage->addHTML( wfMessage( 'smw-semantics-not-enabled' )->text() );
			return;
		}

		if ( $this->getTitle()->getNamespace() === SMW_NS_PROPERTY ) {
			$this->findBasePropertyToRedirectFor( $this->getTitle()->getText() );
		}

		$this->initParameters();

		if ( !isset( $diff ) || !$diffOnly ) {

			// MW 1.25+
			if ( method_exists( $outputPage, 'setIndicators' ) && ( $indicators = $this->getTopIndicators() ) !== '' ) {
				$outputPage->setIndicators( $indicators );
			}

			$outputPage->addHTML( $this->getIntroductoryText() );
		}

		if ( $this->isLockedView === false ) {
			parent::view();
		}

		// Copied from CategoryPage
		$diff = $request->getVal( 'diff' );
		$diffOnly = $request->getBool( 'diffonly', $wgUser->getOption( 'diffonly' ) );
		if ( !isset( $diff ) || !$diffOnly ) {
			$this->showList();
		}
	}

	private function findBasePropertyToRedirectFor( $label ) {

		$property = new DIProperty(
			PropertyRegistry::getInstance()->findPropertyIdByLabel( $label )
		);

		// Ensure to redirect to `Property:Modification date` and not using
		// a possible user contextualized version such as `Property:Date de modification`
		$canonicalLabel = $property->getCanonicalLabel();

		if ( $canonicalLabel !== '' && $label !== $canonicalLabel ) {
			$outputPage = $this->getContext()->getOutput();
			$outputPage->redirect( $property->getCanonicalDiWikiPage()->getTitle()->getFullURL() );
		}
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
	 * @since 2.4
	 *
	 * @return string
	 */
	protected function getIntroductoryText() {
		return '';
	}

	/**
	 * @since 2.4
	 */
	protected function getNavigationLinks( $msgKey, array $diWikiPages, $default = 50 ) {

		$request = $this->getContext()->getRequest();
		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();

		$messageBuilder = $mwCollaboratorFactory->newMessageBuilder(
			$this->getContext()->getLanguage()
		);

		$title = $this->mTitle;
		$title->setFragment( '#SMWResults' ); // Make navigation point to the result list.

		$resultCount = count( $diWikiPages );
		$navigation = '';

		if ( $resultCount > 0 ) {
			$navigation = $messageBuilder->prevNextToText(
				$title,
				intval( $request->getVal( 'limit', $default ) ),
				intval( $request->getVal( 'offset', '0' ) ),
				array(
					'value'  => $request->getVal( 'value', '' ),
					'from'   => $request->getVal( 'from', '' ),
					'until'  => $request->getVal( 'until', '' )
				),
				$resultCount < $request->getVal( 'limit', $default )
			);

			$navigation = Html::rawElement('div', array(), $navigation );
		}

		return Html::rawElement(
			'p',
			array(),
			Html::element( 'span', array(), wfMessage( $msgKey, $resultCount )->parse() ) . '<br>' .
			$navigation
		);
	}

	/**
	 * Main method for adding all additional HTML to the output stream.
	 */
	protected function showList() {

		$outputPage = $this->getContext()->getOutput();
		$request = $this->getContext()->getRequest();

		$this->from = $request->getVal( 'from', '' );
		$this->until = $request->getVal( 'until', '' );

		if ( $this->initParameters() ) {
			$outputPage->addHTML( $this->getHtml() );
			SMWOutputs::commitToOutputPage( $outputPage );
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
