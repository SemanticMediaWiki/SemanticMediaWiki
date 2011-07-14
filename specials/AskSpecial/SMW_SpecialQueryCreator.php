<?php

/**
 * This special page for Semantic MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @file SMW_SpecialQueryCreator.php
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Sergey Chernyshev
 * @author Devayon Das
 */
class SMWQueryCreatorPage extends SMWQueryUI {

	protected $m_params = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'QueryCreator' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	/**
	 * The main entrypoint. Call the various methods of SMWQueryUI and
	 * SMWQueryUIHelper to build ui elements and to process them.
	 *
	 * @global OutputPage $wgOut
	 * @param string $p
	 */
	protected function makePage( $p ) {
		global $wgOut;
		$htmloutput = $this->makeResults( $p );
		if ( $this->uiCore->getQueryString() != "" ) {
			if ( $this->usesNavigationBar() ) {
				$htmloutput .= $this->getNavigationBar ( $this->uiCore->getLimit(), $this->uiCore->getOffset(), $this->uiCore->hasFurtherResults() ); // ? can we preload offset and limit?
			}

			$htmloutput .= "<br/>" . $this->uiCore->getHTMLResult() . "<br>";

			if ( $this->usesNavigationBar() ) {
				$htmloutput .= $this->getNavigationBar ( $this->uiCore->getLimit(), $this->uiCore->getOffset(), $this->uiCore->hasFurtherResults() ); // ? can we preload offset and limit?
			}
		}
		$wgOut->addHTML( $htmloutput );
	}

	/**
	 * Creates the input form
	 *
	 * @global OutputPage $wgOut
	 * @global booolean $smwgQSortingSupport
	 * @return string
	 *
	 * @todo Clean up the lines handling sorting.
	 */
	protected function makeResults() {
		global $wgOut;
		$result = "";
		$spectitle = $this->getTitle();
		$result .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
			'<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';

		$result .= wfMsg( 'smw_qc_query_help' );
		// Main query and printouts.
		$result .= '<p><strong>' . wfMsg( 'smw_ask_queryhead' ) . "</strong></p>\n";
		$result .= '<p>' . $this->getQueryFormBox( $this->uiCore->getQueryString() ) . '</p>';
		// show|hide additional options and querying help
		$result .= '<span id="show_additional_options" style="display:inline"><a href="#addtional" rel="nofollow" onclick="' .
			 "document.getElementById('additional_options').style.display='block';" .
			 "document.getElementById('show_additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_show_addnal_opts' ) . '</a></span>';
		$result .= '<span id="hide_additional_options" style="display:none"><a href="#" rel="nofollow" onclick="' .
			 "document.getElementById('additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='none';" .
			 "document.getElementById('show_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_hide_addnal_opts' ) . '</a></span>';
		$result .= ' | <a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>';
		// additional options
		$result .= '<div id="additional_options" style="display:none">';
		$result .= '<p><strong>' . wfMsg( 'smw_ask_printhead' ) . "</strong></p>\n" .
			'<span style="font-weight: normal;">' . wfMsg( 'smw_ask_printdesc' ) . '</span>' . "\n" .
			'<p>' . $this->getPOFormBox( $this->getPOStrings(), SMWQueryUI::ENABLE_AUTO_SUGGEST ) . '</p>' . "\n";

		// sorting inputs
		$result .= $this->getSortingFormBox();

		$result .= "<br><br>" . $this->getFormatSelectBox( 'broadtable' );

		if ( $this->uiCore->getQueryString() != '' ) // hide #ask if there isnt any query defined
			$result .= $this->getAskEmbedBox();

		$result .= '</div>'; // end of hidden additional options
		$result .= '<br /><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/>' .
			'<input type="hidden" name="eq" value="no"/>' .
			"\n</form>";

	return $result;

	}

	/**
	 * Compatibility method to get the skin; MW 1.18 introduces a getSkin method 
	 * in SpecialPage.
	 *
	 * @since 1.6
	 *
	 * @return Skin
	 */
	public function getSkin() {
		if ( method_exists( 'SpecialPage', 'getSkin' ) ) {
			return parent::getSkin();
		} else {
			global $wgUser;
			return $wgUser->getSkin();
		}
	}

}

