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
	 * @return string
	 */
	protected function makeResults() {
		global $wgOut;
		$result = "";
		$spectitle = $this->getTitle();
		$formatBox = $this->getFormatSelectBoxSep( 'broadtable' );
		$result .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
			'<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';
		$result .= '<br>';
		$result .= wfMsg( 'smw_qc_query_help' );
		// Main query and format options
		$result .= '<table style="width: 100%; ">' .
					'<tr><th>' . wfMsg( 'smw_ask_queryhead' ) . "</th>\n<th>" . wfMsg( 'smw_ask_format_as' ) . "</th></tr>" .
					'<tr>' .
						'<td style="width: 70%; padding-right: 7px;">' . $this->getQueryFormBox() . "</td>\n" .
						'<td style="padding-right: 7px; text-align:center;">' . $formatBox[0] . '</td>' .
					'</tr>' .
					"</table>\n";
		// sorting and prinouts
		$result .= '<div class="smw-qc-sortbox" style="padding-left:10px;">'.$this->getPoSortFormBox().'</div>';
		// show|hide additional options and querying help
		$result .= '<br><span id="show_additional_options" style="display:inline;"><a href="#addtional" rel="nofollow" onclick="' .
			 "document.getElementById('additional_options').style.display='block';" .
			 "document.getElementById('show_additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_show_addnal_opts' ) . '</a></span>';
		$result .= '<span id="hide_additional_options" style="display:none"><a href="#" rel="nofollow" onclick="' .
			 "document.getElementById('additional_options').style.display='none';" .
			 "document.getElementById('hide_additional_options').style.display='none';" .
			 "document.getElementById('show_additional_options').style.display='inline';" . '">' .
			 wfMsg( 'smw_qc_hide_addnal_opts' ) . '</a></span>';
		$result .= ' | <a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>';
		// additional options
		$result .= '<div id="additional_options" style="display:none">';

		$result .= $formatBox[1]; // display the format options

		if ( $this->uiCore->getQueryString() != '' ) // hide #ask if there isnt any query defined
			$result .= $this->getAskEmbedBox();

		$result .= '</div>'; // end of hidden additional options
		$result .= '<br /><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/>' .
			'<input type="hidden" name="eq" value="no"/>' .
			"\n</form>";

	return $result;

	}

	/**
	 * Generates the forms elements(s) for choosing printouts and sorting
	 * options. Use its complement processPoSortFormBox() to decode data
	 * sent by these elements.
	 *
	 * Overrides method from SMWQueryUI (modal window added)
	 *
	 * @return string
	 */
	protected function getPoSortFormBox( $enableAutocomplete = SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
		global $smwgQSortingSupport, $wgRequest, $wgOut, $smwgScriptPath;

		if ( !$smwgQSortingSupport ) return '';
		$this->enableJQueryUI();
		$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-ui/jquery-ui.dialog.min.js" );
		$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-ui/jquery-ui.tabs.min.js" );
		$wgOut->addStyle( "$smwgScriptPath/skins/SMW_custom.css" );

		$result = '';
		$num_sort_values = 0;
		// START: create form elements already submitted earlier via form
		// attempting to load parameters from $wgRequest
		$property_values = $wgRequest->getArray( 'property' );
		$order_values = $wgRequest->getArray( 'order' );
		$display_values = $wgRequest->getArray( 'display' );
		if ( is_array( $property_values ) ) {
			// removing empty values
			foreach ( $property_values as $key => $property_value ) {
				$property_values[$key] = trim( $property_value );
				if ( $property_value == '' ) {
					unset( $property_values[$key] );
				}
			}
		} else {
			/*
			 * Printouts and sorting were set via another widget/form/source, so
			 * create elements by fetching data from $uiCore. The exact ordering
			 * of Ui elements might not be preserved, if the above block were to
			 * be removed. This  is a bit of a hack, converting all strings to
			 * lowercase to simplify searching procedure and using in_array.
			 */

			$po = explode( '?', $this->getPOStrings() );
			reset( $po );
			foreach ( $po as $key => $value ) {
			 $po[$key] = strtolower( trim( $value ) );
			  if ( $po[$key] == '' ) {
				  unset ( $po[$key] );
			  }
			}

			$params = $this->uiCore->getParameters();
			if ( array_key_exists( 'sort', $params ) && array_key_exists( 'order', $params ) ) {
				$property_values = explode( ',', strtolower( $params['sort'] ) );
				$order_values = explode( ',', $params['order'] );
				reset( $property_values );
				reset( $order_values );
			} else {
				$order_values = array(); // do not even show one sort input here
				$property_values = array();
			}

			 foreach ( $po as $po_key => $po_value ) {
				 if ( !in_array( $po_value, $property_values ) ) {
					 $property_values[] = $po_value;
				 }
			 }
			 $display_values = array();
			 reset( $property_values );
			 foreach ( $property_values as $property_key => $property_value ) {
				 if ( in_array( $property_value, $po ) ) {
					 $display_values[$property_key] = "yes";
				 }
			 }
		}
		$num_sort_values = count( $property_values );
		foreach ( $property_values as $i => $property_value ) {

			$result .= Html::openElement( 'div', array( 'id' => "sort_div_$i" ) );
			$result .= '<span class="smw-remove"><a href="javascript:removePOInstance(\'sort_div_' . $i . '\')"><img src="'.$smwgScriptPath.'/skins/images/close-button.png" alt="'. wfMsg('smw_qui_delete').'"></a></span>';
			$result .= wfMsg( 'smw_qui_property' );
			$result .= Html::input( 'property[' . $i . ']', $property_value, 'text', array( 'size' => '35' ) ) . "\n";
			$result .= Html::openElement( 'select', array( 'name' => "order[$i]" ) );

			$if1 = ( !is_array( $order_values ) or !array_key_exists( $i, $order_values ) or $order_values[$i] == 'NONE' );
			$result .= Xml::option( wfMsg( 'smw_qui_nosort' ), "NONE", $if1 );

			$if2 = ( is_array( $order_values ) and array_key_exists( $i, $order_values ) and $order_values[$i] == 'ASC' );
			$result .= Xml::option( wfMsg( 'smw_qui_ascorder' ), "ASC", $if2 );

			$if3 = ( is_array( $order_values ) and array_key_exists( $i, $order_values ) and $order_values[$i] == 'DESC' );
			$result .= Xml::option( wfMsg( 'smw_qui_descorder' ), "DESC", $if3 );

			$result .= Xml::closeElement( 'select' );

			$if4 = ( is_array( $display_values ) and array_key_exists( $i, $display_values ) );
			$result .= Xml::checkLabel( wfMsg( 'smw_qui_shownresults' ), "display[$i]", "display$i", $if4 );

			$result .= Xml::closeElement( 'div' );
		}
		// END: create form elements already submitted earlier via form

		// create hidden form elements to be cloned later
		$hidden = Html::openElement( 'div', array( 'id' => 'sorting_starter', 'class'=>'smw-sort', 'style' => 'display:none' ) ) .
					'<span class="smw-remove"><a><img src="'.$smwgScriptPath.'/skins/images/close-button.png" alt="'. wfMsg('smw_qui_delete').'"></a></span>' .
					wfMsg( 'smw_qui_property' ) .
					Xml::input( "property_num", '35' ) . " ";

		$hidden .= Html::openElement( 'select', array( 'name' => 'order_num' ) );
		$hidden .= Xml::option( wfMsg( 'smw_qui_nosort' ), 'NONE' );
		$hidden .= Xml::option( wfMsg( 'smw_qui_ascorder' ), 'ASC' );
		$hidden .= Xml::option( wfMsg( 'smw_qui_descorder' ), 'DESC' );
		$hidden .= Xml::closeElement( 'select' );

		$hidden .= Xml::checkLabel( wfMsg( 'smw_qui_shownresults' ), "display_num", '', true );
		$hidden .= Xml::closeElement( 'div' );

		$hidden = json_encode( $hidden );

		$dialogbox = Xml::openElement( 'div', array( 'id' => 'dialog', 'title' => 'Advanced Print-Out Options' ) ) . // todo i18n
			Xml::checkLabel( wfMsg( 'smw_qui_shownresults' ), '', 'dialog-show-results', true ) .
			'<div id="tab-box">' .
				'<ul>' .
					'<li><a href="#property-tab">Property</a></li>' . // todo i18n
					'<li><a href="#category-tab">Category</a></li>' . // todo i18n
				'</ul>' .
				'<div id="property-tab">' .
					Xml::inputLabel( 'Property', '', 'tab-property', 'tab-property' ) . '<br/>' . // todo i18n
					Xml::inputLabel( 'Label (optional):', '', 'tab-property-label', 'tab-property-label' ) . '<br/>' . // todo i18n
					'Format: ' . Html::openElement( 'select', array( 'name' => 'tab-format' ) ) . // todo i18n
						Xml::option( 'None (default)', 'NONE' ) . // todo i18n
						Xml::option( 'Simple', '-' ) . // todo i18n
						Xml::option( 'Numeric', 'n' ) . // todo i18n
						Xml::option( 'Unit', 'u' ) . // todo i18n
						Xml::option( 'Custom', 'CUSTOM' ) . // todo i18n
					Xml::closeElement( 'select' ) .
					Xml::input( 'format-custom' ) . '<br/>' .
					Xml::inputLabel( 'limit (optional):', '', 'tab-property-limit', 'tab-property-limit' ) . '<br/>' . // todo i18n
				'</div>' .
				'<div id="category-tab">' .
					Xml::inputLabel( 'Label (optional):', '', 'tab-category-label', 'tab-category-label' ) . '<br/>' . // todo i18n
					Xml::inputLabel( 'Specify a category (optional)', '', 'tab-category', 'tab-category' ) . '<br/>' . // todo i18n
					'If result belongs to category, display' . Html::input( 'tab-yes', 'X' ) . '<br/>' .
					'else display' . Html::input( 'tab-yes', ' ' ) .
				'</div>' .
			'</div>' .
			'<br>Sort by: <select id ="dialog-order">' . // todo i18n
				'<option value="NONE">' . wfMsg( 'smw_qui_nosort' ) . '</option>' .
				'<option value="ASC">' . wfMsg( 'smw_qui_ascorder' ) . '</option>' .
				'<option value="DESC">' . wfMsg( 'smw_qui_descorder' ) . '</option>' .
			'</select>' . '</form></div>';

		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '[<a href="javascript:addPOInstance(\'sorting_starter\', \'sorting_main\')">' . wfMsg( 'smw_qui_addnprop' ) . '</a>]' . "\n";

		// Javascript code for handling adding and removing the "sort" inputs
		if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
			$this->addAutocompletionJavascriptAndCSS();
		}
		$javascript_text = <<<EOT
<script type="text/javascript">
// code for handling adding and removing the "sort" inputs

jQuery(function(){
	jQuery('$hidden').appendTo(document.body);
	jQuery('$dialogbox').appendTo(document.body);
	jQuery('#dialog').dialog({
		autoOpen: false,
		modal: true,
		resizable: true,
		minHeight: 300,
		minWidth: 500
	});
	jQuery('#tab-box').tabs({
		selected:1
	});
});
jQuery(document).ready(function(){
	jQuery('#sort-more').click(function(){jQuery('#dialog').dialog("open");});
	jQuery('#dialog-show-results').click(function(){
		if(jQuery('#dialog-show-results')[0].checked){
			jQuery('#tab-box').show('blind');
		} else {
			jQuery('#tab-box').hide('blind');
		}
	});
});
function smw_makeDialog(prop_id){
		\$j('#tab-property')[0].value=\$j('#property'+prop_id)[0].value;
		\$j('#dialog').dialog('open');
}

var num_elements = {$num_sort_values};

function addPOInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.id = div_id;
	new_div.style.display = 'block';

	var children = new_div.getElementsByTagName('*');
	var x;
	for (x = 0; x < children.length; x++) {
		if (children[x].name){
			children[x].id = children[x].name.replace(/_num/, ''+num_elements);
			children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
		}
	}

	//Create 'more' link
	var more_button =document.createElement('span');
	more_button.innerHTML = ' <a class="smwq-more" href="javascript:smw_makeDialog(\'' + num_elements + '\')">more</a> '; //TODO: i18n
	more_button.id = 'more'+num_elements;
	new_div.appendChild(more_button);

	//Add the new instance
	main_div.appendChild(new_div);

	// initialize delete button
	st='sort_div_'+num_elements;
	jQuery('#'+new_div.id).find(".smw-remove a")[0].href="javascript:removePOInstance('"+st+"')";
EOT;
	if ( $enableAutocomplete == SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
		$javascript_text .= <<<EOT
	//add autocomplete
	jQuery('[name*="property"]').autocomplete({
		minLength: 2,
		source: function(request, response) {
			url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm';

			jQuery.getJSON(url, 'search='+request.term, function(data){
				//remove the namespace prefix 'Property:' from returned data
				for(i=0;i<data[1].length;i++) data[1][i]=data[1][i].substr(data[1][i].indexOf(':')+1);
				response(data[1]);
			});
		}
	});
EOT;
		}
	$javascript_text .= <<<EOT
	num_elements++;

}

function removePOInstance(div_id) {
	var olddiv = document.getElementById(div_id);
	var parent = olddiv.parentNode;
	parent.removeChild(olddiv);
}
</script>

EOT;

		$wgOut->addScript( $javascript_text );
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

