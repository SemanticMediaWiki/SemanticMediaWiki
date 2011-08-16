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
		$htmlOutput = $this->makeResults( $p );
		if ( $this->uiCore->getQueryString() != "" ) {
			if ( $this->usesNavigationBar() ) {
				$htmlOutput .= Html::rawElement( 'div', array( 'class' => 'smwqcnavbar' ),
									$this->getNavigationBar ( $this->uiCore->getLimit(), $this->uiCore->getOffset(), $this->uiCore->hasFurtherResults() )
								);
			}

			$htmlOutput .= Html::rawElement( 'div', array( 'class' => 'smwqcresult' ), $this->uiCore->getHTMLResult() );

			if ( $this->usesNavigationBar() ) {
				$htmlOutput .= Html::rawElement( 'div', array( 'class' => 'smwqcnavbar' ),
									$this->getNavigationBar ( $this->uiCore->getLimit(), $this->uiCore->getOffset(), $this->uiCore->hasFurtherResults() )
								);
			}
		}
		$wgOut->addHTML( $htmlOutput );
	}

	/**
	 * This method should call the various processXXXBox() methods for each of
	 * the corresponding getXXXBox() methods which the UI uses.
	 * Merge the results of these methods and return them.
	 *
	 * @global WebRequest $wgRequest
	 * @return array
	 */
	protected function processParams() {
		global $wgRequest;
		$params = array_merge(
							array(
							'format'  =>  $wgRequest->getVal( 'format' ),
							'offset'  =>  $wgRequest->getVal( 'offset',  '0'  ),
							'limit'   =>  $wgRequest->getVal( 'limit',   '20' ) ),
							$this->processPoSortFormBox( $wgRequest ),
							$this->processFormatSelectBox( $wgRequest ),
							$this->processMainLabelFormBox( $wgRequest )
				);
		return $params;
	}

	/**
	 * A method which decodes form data sent through form-elements generated
	 * by its complement, getMainLabelFormBoxSep().
	 *
	 * @param WebRequest $wgRequest
	 * @return array
	 */
	protected function processMainLabelFormBox( WebRequest $wgRequest ) {
		$mainLabel = $wgRequest->getVal( 'pmainlabel', '' );
		$result = array( 'mainlabel' => $mainLabel );
		return $result;
	}

	/**
	 * Generates the form elements for the main-label parameter.  Use its
	 * complement processMainLabelFormBox() to decode data sent through these
	 * elements.
	 *
	 * @global string $smwgScriptPath
	 * @global OutputPage $wgOut
	 * @return array the first element has the link to enable mainlabel, and the second gives the mainlabel control
	 */
	protected function getMainLabelFormBoxSep() {
		global $smwgScriptPath, $wgOut;
		$result = array();
		$param = $this->uiCore->getParameters();
		if ( is_array( $param ) && array_key_exists( 'mainlabel', $param ) ) {
			$mainLabel = $param['mainlabel'];
		} else {
			$mainLabel = '';
		}
		if ( $mainLabel == '-' ) {
			$mainLabelText = '';
			$linkDisplay = 'inline';
			$formDisplay = 'none';
		} else {
			$mainLabelText = $mainLabel;
			$linkDisplay = 'none';
			$formDisplay = 'block';
		}
		if ( $this->uiCore->getQueryString() == '' ) {
			$linkDisplay = 'inline';
			$formDisplay = 'none';
		}

		$this->enableJQuery();
		$javascriptText = <<<EOT
<script type="text/javascript">

	function smwRemoveMainLabel(){
		jQuery('#mainlabelhid').attr('value','-');
		jQuery('#mainlabelvis').attr('value','');
		jQuery('#add_mainlabel').show();
		jQuery('#smwmainlabel').hide();
	}
	function smwAddMainLabel(){
		jQuery('#mainlabelhid').attr('value','');
		jQuery('#mainlabelvis').attr('value','');
		jQuery('#smwmainlabel').show();
		jQuery('#add_mainlabel').hide();
	}
	jQuery(document).ready(function(){
		jQuery('#mainlabelvis').bind('change', function(){
			jQuery('#mainlabelhid').attr('value',jQuery('#mainlabelvis').attr('value'));
		});
	});
</script>
EOT;
		$wgOut->addScript( $javascriptText );
		$result[0] = Html::openElement( 'span', array( 'id' => 'add_mainlabel', 'style' => "display:$linkDisplay;" ) ) .
						'[' . Html::element( 'a', array( 'href' => 'javascript:smwAddMainLabel()',
							'rel' => 'nofollow', 'id' => 'add_mainlabel' ),
							wfMsg( 'smw_qc_addmainlabel' ) ) .
						']</span>';

		$result[1] = Html::openElement( 'div', array( 'id' => 'smwmainlabel', 'class' => 'smwsort', 'style' => "display:$formDisplay;" ) ) .
						Html::openElement( 'span', array( 'class' => 'smwquisortlabel' ) ) .
						Html::openElement( 'span', array( 'class' => 'smw-remove' ) ) .
						Html::openElement( 'a', array( 'href' => 'javascript:smwRemoveMainLabel()' ) ) .
						'<img src="' . $smwgScriptPath . '/skins/images/close-button.png" alt="' . wfMsg( 'smw_qui_delete' ) . '">' .
						'</a>' .
						'</span>' .
						'<strong>' . wfMsg( 'smw_qc_mainlabel' ) . '</strong>' .
						'</span>' .
						'<input size="25" value="' . $mainLabelText . '" id="mainlabelvis" />' .
						Html::hidden( 'pmainlabel', $mainLabel, array( 'id' => 'mainlabelhid' ) ) .
						'</div>';
		return $result;
	}

	/**
	 * Displays a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @param string $format
	 * @param array $paramValues The current values for the parameters (name => value)
	 * @param array $ignoredAttribs Attributes which should not be generated by this method.
	 *
	 * @return string
	 *
	 * Overridden from parent to ignore GUI parameters 'format' 'limit' and 'offset'
	 */
	protected function showFormatOptions( $format, array $paramValues, array $ignoredAttribs = array() ) {
		return parent::showFormatOptions( $format, $paramValues, array( 'format', 'limit', 'offset', 'mainlabel' ) );
	}
	/**
	 * Creates the input form
	 *
	 * @global OutputPage $wgOut
	 * @global string $smwgScriptPath
	 * @return string
	 */
	protected function makeResults() {
		global $wgOut, $smwgScriptPath;
		$this->enableJQuery();
		$result = "";
		$specTitle = $this->getTitle();
		$formatBox = $this->getFormatSelectBoxSep( 'broadtable' );
		$result .= '<form name="ask" action="' . $specTitle->escapeLocalURL() . '" method="get">' . "\n" .
			'<input type="hidden" name="title" value="' . $specTitle->getPrefixedText() . '"/>';
		$result .= '<br/>';
		$result .= wfMsg( 'smw_qc_query_help' );
		// Main query and format options
		$result .= $this->getQueryFormBox();
		// sorting and prinouts
		$mainLabelHtml = $this->getMainLabelFormBoxSep();
		$result .= '<div class="smwqcsortbox">' . $mainLabelHtml[1] . $this->getPoSortFormBox() . $mainLabelHtml[0] . '</div>';
		// additional options
		// START: show|hide additional options
		$result .= '<div class="smwqcformatas"><strong>' . wfMsg( 'smw_ask_format_as' ) . '</strong>';
		$result .= $formatBox[0] . '<span id="show_additional_options" style="display:inline;"><a href="#addtional" rel="nofollow" onclick="' .
			 "jQuery('#additional_options').show('blind');" .
			 "document.getElementById('show_additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_show_addnal_opts' ) . '</a></span>';
		$result .= '<span id="hide_additional_options" style="display:none"><a href="#" rel="nofollow" onclick="' .
			 "jQuery('#additional_options').hide('blind');;" .
			 "document.getElementById('hide_additional_options').style.display='none';" .
			 "document.getElementById('show_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_hide_addnal_opts' ) . '</a></span>';
		$result .= '</div>';
		// END: show|hide additional options
		$result .= '<div id="additional_options" style="display:none">';

		$result .= $formatBox[1]; // display the format options

		$result .= '</div>'; // end of hidden additional options
		$result .= '<br /><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/><br/>';

		$result .= '<a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>';
		if ( $this->uiCore->getQueryString() != '' ) { // hide #ask if there isnt any query defined
			$result .= ' | <a name="show-embed-code" id="show-embed-code" href="##" rel="nofollow">' . wfMsg( 'smw_ask_show_embed' ) . '</a>';
			$result .= '<div id="embed-code-dialog">' .
						$this->getAskEmbedBox() .
						'</div>';
					$this->enableJQueryUI();
		$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-ui/jquery-ui.dialog.min.js" );
		$wgOut->addStyle( "$smwgScriptPath/skins/SMW_custom.css" );
				$javascriptText = <<<EOT
<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery('#embed-code-dialog').dialog({
		autoOpen:false,
		buttons:{
			Ok: function(){
				jQuery(this).dialog("close");
			}
		}
	});
	jQuery('#show-embed-code').bind('click', function(){
		jQuery('#embed-code-dialog').dialog("open");
	});
});
</script>
EOT;
			$wgOut->addScript( $javascriptText );
		}
		$result .= '<input type="hidden" name="eq" value="no"/>' .
			"\n</form><br/>";

	return $result;

	}
}

