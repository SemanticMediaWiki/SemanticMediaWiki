<?php

namespace SMW;

use OutputPage;
use Skin;
use Title;

/**
 * BeforePageDisplay hook which allows last minute changes to the
 * output page, e.g. adding of CSS or JavaScript
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BeforePageDisplay extends FunctionHook {

	/** @var OutputPage */
	protected $outputPage = null;

	/** @var Skin */
	protected $skin = null;

	/**
	 * @since  1.9
	 *
	 * @param OutputPage &$outputPage
	 * @param Skin &$skin
	 */
	public function __construct( OutputPage &$outputPage, Skin &$skin ) {
		$this->outputPage = $outputPage;
		$this->skin = $skin;
	}

	/**
	 * @see HookBase::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$title = $this->outputPage->getTitle();

		// Add style resources to avoid unstyled content
		$this->outputPage->addModules( array( 'ext.smw.style' ) );

		// Add export link to the head
		if ( $title instanceof Title && !$title->isSpecialPage() ) {
			$linkarr['rel']   = 'ExportRDF';
			$linkarr['type']  = 'application/rdf+xml';
			$linkarr['title'] = $title->getPrefixedText();
			$linkarr['href']  = \SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getLocalUrl( 'xmlmime=rdf' );
			$this->outputPage->addLink( $linkarr );
		}

		return true;
	}

}
