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
	 * Overwrite view() from Article.php to add additional HTML to the
	 * output.
	 */
	public function view() {
		global $wgRequest, $wgUser;

		$outputPage = $this->getContext()->getOutput();

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
		global $wgRequest;

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
				$wgRequest->getVal( 'limit', $default ),
				$wgRequest->getVal( 'offset', '0' ),
				array(
					'value'  => $wgRequest->getVal( 'value', '' ),
					'from'   => $wgRequest->getVal( 'from', '' ),
					'until'  => $wgRequest->getVal( 'until', '' )
				),
				$resultCount < $wgRequest->getVal( 'limit', $default )
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
