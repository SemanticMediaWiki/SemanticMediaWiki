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
		$result .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
			'<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';
		$result .= '<br>';
		$result .= wfMsg( 'smw_qc_query_help' );
		// Main query and printouts.
		$result .= '<p><strong>' . wfMsg( 'smw_ask_queryhead' ) . "</strong></p>\n";
		$result .= '<p>' . $this->getQueryFormBox() . '</p>';
		// sorting and prinouts
		$result .= $this->getPoSortFormBox();
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

		$result .= $this->getFormatSelectBox( 'broadtable' );

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
		$wgOut->addScriptFile( "$smwgScriptPath/libs/jquery-ui/jquery-ui.min.dialog.js" );

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
			$result .= Html::openElement( 'div', array( 'id' => "sort_div_$i" ) ) . wfMsg( 'smw_qui_property' );
			$result .= Html::input( 'property[' . $i . ']', $property_value, 'text', array( 'size' => '35' ) ) . "\n";
			$result .= html::openElement( 'select', array( 'name' => "order[$i]" ) );
			if ( !is_array( $order_values ) or !array_key_exists( $i, $order_values ) or $order_values[$i] == 'NONE' ) {
				$result .= '<option selected value="NONE">' . wfMsg( 'smw_qui_nosort' ) . "</option>\n";
			} else {
				$result .= '<option          value="NONE">' . wfMsg( 'smw_qui_nosort' ) . "</option>\n";
			}
			if ( is_array( $order_values ) and array_key_exists( $i, $order_values ) and $order_values[$i] == 'ASC' ) {
				$result .= '<option selected value="ASC">' . wfMsg( 'smw_qui_ascorder' ) . "</option>\n";
			} else {
				$result .= '<option          value="ASC">' . wfMsg( 'smw_qui_ascorder' ) . "</option>\n";
			}
			if ( is_array( $order_values ) and array_key_exists( $i, $order_values ) and $order_values[$i] == 'DESC' ) {
				$result .= '<option selected value="DESC">' . wfMsg( 'smw_qui_descorder' ) . "</option>\n";
			} else {
				$result .= '<option          value="DESC">' . wfMsg( 'smw_qui_descorder' ) . "</option>\n";
			}
			$result .= "</select> \n";
			if ( is_array( $display_values ) and array_key_exists( $i, $display_values ) ) {
				$result .= '<input type="checkbox" checked name="display[' . $i . ']" value="yes">' . wfMsg( 'smw_qui_shownresults' ) . "\n";
			} else {
				$result .= '<input type="checkbox"         name="display[' . $i . ']" value="yes">' . wfMsg( 'smw_qui_shownresults' ) . "\n";
			}
			$result .= '[<a href="javascript:removePOInstance(\'sort_div_' . $i . '\')">' . wfMsg( 'smw_qui_delete' ) . '</a>]' . "\n";
			$result .= "</div> \n";
		}
		// END: create form elements already submitted earlier via form

		// create hidden form elements to be cloned later
		$hidden =  '<div id="sorting_starter" style="display: none">' . wfMsg( 'smw_qui_property' ) .
					' <input type="text" size="35" name="property_num" />';
		$hidden .= ' <select name="order_num">';
		$hidden .= '	<option value="NONE">' . wfMsg( 'smw_qui_nosort' ) . '</option>';
		$hidden .= '	<option value="ASC">' . wfMsg( 'smw_qui_ascorder' ) . '</option>';
		$hidden .= '	<option value="DESC">' . wfMsg( 'smw_qui_descorder' ) . '</option></select>';
		$hidden .= '<input type="checkbox" checked name="display_num" value="yes">' . wfMsg( 'smw_qui_shownresults' );
		$hidden .= '</div>';

		$dialogbox = '<div id="dialog"><form>' .
			'<input type="checkbox" checked id="dialog-show-results" >' . wfMsg( 'smw_qui_shownresults' ) . '<fieldset id="show-result-box">' .
			'<br>Label:<input id="sort-label"><br> still under construction </fieldset>' . //TODO; remove
			'<br><select id ="dialog-order">' .
				'<option value="NONE">' . wfMsg( 'smw_qui_nosort' ) . '</option>' .
				'<option value="ASC">' . wfMsg( 'smw_qui_ascorder' ) . '</option>' .
				'<option value="DESC">' . wfMsg( 'smw_qui_descorder' ) . '</option>' .
			'</select>' . '</form></div>';

		$result .= '<div id="sorting_main"></div>' . "\n";
		$result .= '[<a href="javascript:addPOInstance(\'sorting_starter\', \'sorting_main\')">' . wfMsg( 'smw_qui_addnprop' ) . '</a>]' . "\n";

		// Javascript code for handling adding and removing the "sort" inputs
		$delete_msg = wfMsg( 'smw_qui_delete' );

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
		modal: true
	});
});
jQuery(document).ready(function(){
	jQuery('#sort-more').click(function(){jQuery('#dialog').dialog("open");});
	jQuery('#dialog-show-results').click(function(){
		if(jQuery('#dialog-show-results')[0].checked){
			//alert("hello");
			jQuery('#show-result-box').show('blind');
		} else {
			jQuery('#show-result-box').hide('blind');
		}
	});
});
function smw_makeDialog(prop_id){
		\$j('#sort-label')[0].value=\$j('#property'+prop_id)[0].value;
		\$j('#dialog').dialog('open');
}

var num_elements = {$num_sort_values};

function addPOInstance(starter_div_id, main_div_id) {
	var starter_div = document.getElementById(starter_div_id);
	var main_div = document.getElementById(main_div_id);

	//Create the new instance
	var new_div = starter_div.cloneNode(true);
	var div_id = 'sort_div_' + num_elements;
	new_div.className = 'multipleTemplate';
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
	new_div.appendChild(more_button);
	//Create 'delete' link
	var remove_button = document.createElement('span');
	remove_button.innerHTML = '[<a class="smwq-remove" href="javascript:removePOInstance(\'sort_div_' + num_elements + '\')">{$delete_msg}</a>]';
	new_div.appendChild(remove_button);

	//Add the new instance
	main_div.appendChild(new_div);
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

