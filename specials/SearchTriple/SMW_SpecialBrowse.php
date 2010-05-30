<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * A factbox like view on an article, implemented by a special page.
 *
 * @author Denny Vrandecic
 */

/**
 * A factbox view on one specific article, showing all the Semantic data about it
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWSpecialBrowse extends SpecialPage {

	/// int How  many incoming values should be asked for
	static public $incomingvaluescount = 8;
	/// int  How many incoming properties should be asked for
	static public $incomingpropertiescount = 21;
	/// SMWDataValue  Topic of this page
	private $subject = null;
	/// Text to be set in the query form
	private $articletext = "";
	/// bool  To display outgoing values?
	private $showoutgoing = true;
	/// bool  To display incoming values?
	private $showincoming = false;
	/// int  At which incoming property are we currently?
	private $offset = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $smwgBrowseShowAll;
		parent::__construct( 'Browse', '', true, false, 'default', true );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		if ( $smwgBrowseShowAll ) {
			SMWSpecialBrowse::$incomingvaluescount = 21;
			SMWSpecialBrowse::$incomingpropertiescount = - 1;
		}
	}

	/**
	 * Main entry point for Special Pages
	 *
	 * @param[in] $query string  Given by MediaWiki
	 */
	public function execute( $query ) {
		global $wgRequest, $wgOut, $smwgBrowseShowAll;
		$this->setHeaders();
		// get the GET parameters
		$this->articletext = $wgRequest->getVal( 'article' );
		// no GET parameters? Then try the URL
		if ( $this->articletext == '' ) {
			$params = SMWInfolink::decodeParameters( $query, false );
			reset( $params );
			$this->articletext = current( $params );
		}
		$this->subject = SMWDataValueFactory::newTypeIDValue( '_wpg', $this->articletext );
		$offsettext = $wgRequest->getVal( 'offset' );
		$this->offset = ( $offsettext == '' ) ? 0:intval( $offsettext );
		$dir = $wgRequest->getVal( 'dir' );
		if ( $smwgBrowseShowAll ) {
			$this->showoutgoing = true;
			$this->showincoming = true;
		}
		if ( ( $dir == 'both' ) || ( $dir == 'in' ) ) $this->showincoming = true;
		if ( $dir == 'in' ) $this->showoutgoing = false;
		if ( $dir == 'out' ) $this->showincoming = false;

		$wgOut->addHTML( $this->displayBrowse() );
		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
	}

	/**
	 * Create and output HTML including the complete factbox, based on the extracted
	 * parameters in the execute comment.
	 *
	 * @return string  A HTML string with the factbox
	 */
	private function displayBrowse() {
		global $wgContLang, $wgOut;
		$html = "\n";
		$leftside = !( $wgContLang->isRTL() ); // For right to left languages, all is mirrored
		if ( $this->subject->isValid() ) {
			$wgOut->addStyle( '../extensions/SemanticMediaWiki/skins/SMW_custom.css' );

			$html .= $this->displayHead();
			if ( $this->showoutgoing ) {
				$data = smwfGetStore()->getSemanticData( $this->subject );
				$html .= $this->displayData( $data, $leftside );
				$html .= $this->displayCenter();
			}
			if ( $this->showincoming ) {
				list( $indata, $more ) = $this->getInData();
				global $smwgBrowseShowInverse;
				if ( !$smwgBrowseShowInverse ) $leftside = !$leftside;
				$html .= $this->displayData( $indata, $leftside, true );
				$html .= $this->displayBottom( $more );
			}

			$this->articletext = $this->subject->getWikiValue();
			// Add a bit space between the factbox and the query form
			if ( !$this->including() ) $html .= "<p> &#160; </p>\n";
		}
		if ( !$this->including() ) $html .= $this->queryForm();
		$wgOut->addHTML( $html );
	}

	/**
	 * Creates the HTML table displaying the data of one subject.
	 *
	 * @param[in] $data SMWSemanticData  The data to be displayed
	 * @param[in] $left bool  Should properties be displayed on the left side?
	 * @param[in] $incoming bool  Is this an incoming? Or an outgoing?
	 * @return A string containing the HTML with the factbox
	 */
	private function displayData( SMWSemanticData $data, $left = true, $incoming = false ) {
		global $wgUser;
		$skin = $wgUser->getSkin();
		// Some of the CSS classes are different for the left or the right side.
		// In this case, there is an "i" after the "smwb-". This is set here.
		$inv = $left ? '':'i';
		$html = "<table class=\"smwb-" . $inv . "factbox\" cellpadding=\"0\" cellspacing=\"0\">\n";
		$properties = $data->getProperties();
		$noresult = true;
		foreach ( $properties as $property ) {
			$displayline = true;
			if ( $property->isVisible() ) {
				$property->setCaption( $this->getPropertyLabel( $property, $incoming ) );
				$proptext = $property->getShortHTMLText( $skin ) . "\n";
			} elseif ( $property->getPropertyID() == '_INST' ) {
				$proptext = $skin->specialLink( 'Categories' );
			} elseif ( $property->getPropertyID() == '_REDI' ) {
				$proptext = $skin->specialLink( 'Listredirects', 'isredirect' );
			} else {
				$displayline = false;
			}
			if ( $displayline ) {
				$head  = "<th>" . $proptext . "</th>\n";
				// display values
				$body  = "<td>\n";
				$values = $data->getPropertyValues( $property );
				$count = count( $values );
				$more = ( $count >= SMWSpecialBrowse::$incomingvaluescount );
				foreach ( $values as $value ) {
					if ( ( $count == 1 ) && $more && $incoming ) {
						// if there are more incoming values than a certain treshold, display a link to the rest instead
						$body .= '<a href="' . $skin->makeSpecialUrl( 'SearchByProperty', 'property=' . urlencode( $property->getWikiValue() ) . '&value=' . urlencode( $data->getSubject()->getWikiValue() ) ) . '">' . wfMsg( "smw_browse_more" ) . "</a>\n";
					} else {
						$body .= "<span class=\"smwb-" . $inv . "value\">" .
						         $this->displayValue( $property, $value, $incoming ) . "</span>";
					}
					$count--;
					$body .= ( $count > 0 ) ? ", \n":"\n";
				} // end foreach values
				$body .= "</td>\n";

				// display row
				$html .= "<tr class=\"smwb-" . $inv . "propvalue\">\n" .
				         ( $left ? ( $head . $body ):( $body . $head ) ) . "</tr>\n";
				$noresult = false;
			}
		} // end foreach properties
		if ( $noresult ) {
			$html .= "<tr class=\"smwb-propvalue\"><th> &#160; </th><td><em>" .
			         wfMsg( $incoming ? 'smw_browse_no_incoming':'smw_browse_no_outgoing' ) . "</em></td></tr>\n";
		}
		$html .= "</table>\n";
		return $html;
	}

	/**
	 * Displays a value, including all relevant links (browse and search by property)
	 *
	 * @param[in] $property SMWPropertyValue  The property this value is linked to the subject with
	 * @param[in] $value SMWDataValue  The actual value
	 * @param[in] $incoming bool  If this is an incoming or outgoing link
	 * @return string  HTML with the link to the article, browse, and search pages
	 */
	private function displayValue( SMWPropertyValue $property, SMWDataValue $value, $incoming ) {
		global $wgUser;
		$skin = $wgUser->getSkin();
		$html = $value->getLongHTMLText( $skin );
		if ( $value->getTypeID() == '_wpg' ) {
			$html .= "&#160;" . SMWInfolink::newBrowsingLink( '+', $value->getLongWikiText() )->getHTML( $skin );
		} elseif ( $incoming && $property->isVisible() ) {
			$html .= "&#160;" . SMWInfolink::newInversePropertySearchLink( '+', $value->getTitle(), $property->getText(), 'smwsearch' )->getHTML( $skin );
		} else {
			$html .= $value->getInfolinkText( SMW_OUTPUT_HTML, $skin );
		}
		return $html;
	}

	/**
	 * Displays the subject that is currently being browsed to.
	 *
	 * @return A string containing the HTML with the subject line
	 */
	 private function displayHead() {
	 	global $wgUser, $wgOut;
	 	$skin = $wgUser->getSkin();
		$wgOut->setHTMLTitle( $this->subject->getTitle() );
		$html  = "<table class=\"smwb-factbox\" cellpadding=\"0\" cellspacing=\"0\">\n";
		$html .= "<tr class=\"smwb-title\"><td colspan=\"2\">\n";
		$html .= $skin->makeLinkObj( $this->subject->getTitle() ) . "\n"; // @todo Replace makeLinkObj with link as soon as we drop MW1.12 compatibility
		$html .= "</td></tr>\n";
		$html .= "</table>\n";
		return $html;
	 }

	/**
	 * Creates the HTML for the center bar including the links with further navigation options.
	 *
	 * @return string  HTMl with the center bar
	 */
	private function displayCenter() {
		return "<a name=\"smw_browse_incoming\"></a>\n" .
		       "<table class=\"smwb-factbox\" cellpadding=\"0\" cellspacing=\"0\">\n" .
		       "<tr class=\"smwb-center\"><td colspan=\"2\">\n" .
		       ( $this->showincoming ?
			     $this->linkhere( wfMsg( 'smw_browse_hide_incoming' ), true, false, 0 ):
		         $this->linkhere( wfMsg( 'smw_browse_show_incoming' ), true, true, $this->offset ) ) .
		       "&#160;\n" . "</td></tr>\n" . "</table>\n";
	}

	/**
	 * Creates the HTML for the bottom bar including the links with further navigation options.
	 *
	 * @param[in] $more bool  Are there more inproperties to be displayed?
	 * @return string  HTMl with the bottom bar
	 */
	private function displayBottom( $more ) {
		$html  = "<table class=\"smwb-factbox\" cellpadding=\"0\" cellspacing=\"0\">\n" .
		         "<tr class=\"smwb-center\"><td colspan=\"2\">\n";
		$sometext = false;
		global $smwgBrowseShowAll;
		if ( !$smwgBrowseShowAll ) {
			if ( ( $this->offset > 0 ) || $more ) {
				$offset = max( $this->offset - SMWSpecialBrowse::$incomingpropertiescount + 1, 0 );
				$html .= ( $this->offset == 0 ) ? wfMsg( 'smw_result_prev' ):
					     $this->linkhere( wfMsg( 'smw_result_prev' ), $this->showoutgoing, true, $offset );
				$offset = $this->offset + SMWSpecialBrowse::$incomingpropertiescount - 1;
				$html .= " &#160;&#160;&#160;  <strong>" . wfMsg( 'smw_result_results' ) . " " . ( $this->offset + 1 ) .
						 " – " . ( $offset ) . "</strong>  &#160;&#160;&#160; ";
				$html .= $more ? $this->linkhere( wfMsg( 'smw_result_next' ), $this->showoutgoing, true, $offset ):wfMsg( 'smw_result_next' );
			}
		}
		$html .= "&#160;\n" . "</td></tr>\n" . "</table>\n";
		return $html;
	}

	/**
	 * Creates the HTML for a link to this page, with some parameters set.
	 *
	 * @param[in] $text string  The anchor text for the link
	 * @param[in] $out bool  Should the linked to page include outgoing properties?
	 * @param[in] $in bool  Should the linked to page include incoming properties?
	 * @param[in] $offset int  What is the offset for the incoming properties?
	 * @return string  HTML with the link to this page
	 */
	private function linkhere( $text, $out, $in, $offset ) {
	 	global $wgUser;
	 	$skin = $wgUser->getSkin();
		$dir = $out ? ( $in ? 'both':'out' ):'in';
		$frag = ( $text == wfMsg( 'smw_browse_show_incoming' ) ) ? '#smw_browse_incoming':'';
		return '<a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'Browse',
		         "offset={$offset}&dir={$dir}&article=" . urlencode( $this->subject->getLongWikiText() ) ) ) . "$frag\">$text</a>";
	}

	/**
	 * Creates a Semantic Data object with the incoming properties instead of the
	 * usual outproperties.
	 *
	 * @return array(SMWSemanticData, bool)  The semantic data including all inproperties, and if there are more inproperties left
	 */
	private function getInData() {
		$indata = new SMWSemanticData( $this->subject );
		$options = new SMWRequestOptions();
		$options->sort = true;
		$options->limit = SMWSpecialBrowse::$incomingpropertiescount;
		if ( $this->offset > 0 ) $options->offset = $this->offset;
		$inproperties = smwfGetStore()->getInProperties( $this->subject, $options );
		$more = ( count( $inproperties ) == SMWSpecialBrowse::$incomingpropertiescount );
		if ( $more ) array_pop( $inproperties ); // drop the last one
		$valoptions = new SMWRequestOptions();
		$valoptions->sort = true;
		$valoptions->limit = SMWSpecialBrowse::$incomingvaluescount;
		foreach ( $inproperties as $property ) {
			$values = smwfGetStore()->getPropertySubjects( $property, $this->subject, $valoptions );
			foreach ( $values as $value ) {
				$indata->addPropertyObjectValue( $property, $value );
			}
		}
		return array( $indata, $more );
	}

	/**
	 * Figures out the label of the property to be used. For outgoing ones it is just
	 * the text, for incoming ones we try to figure out the inverse one if needed,
	 * either by looking for an explicitly stated one or by creating a default one.
	 *
	 * @param[in] $property SMWPropertyValue  The property of interest
	 * @param[in] $incoming bool  If it is an incoming property
	 * @return string  The label of the property
	 */
	private function getPropertyLabel( SMWPropertyValue $property, $incoming = false ) {
		global $smwgBrowseShowInverse;
		if ( $incoming && $smwgBrowseShowInverse ) {
			$oppositeprop = SMWPropertyValue::makeProperty( wfMsg( 'smw_inverse_label_property' ) );
			$labelarray = &smwfGetStore()->getPropertyValues( $property->getWikiPageValue(), $oppositeprop );
			$rv = ( count( $labelarray ) > 0 ) ? $labelarray[0]->getLongWikiText():
			       wfMsg( 'smw_inverse_label_default', $property->getWikiValue() );
		} else {
			$rv = $property->getWikiValue();
		}
		return $this->unbreak( $rv );
	}

	/**
	 * Creates the query form in order to quickly switch to a specific article.
	 *
	 * @return A string containing the HTML for the form
	 */
	private function queryForm() {
		$title = Title::makeTitle( NS_SPECIAL, 'Browse' );
		return '  <form name="smwbrowse" action="' . $title->escapeLocalURL() . '" method="get">' . "\n" .
		       '    <input type="hidden" name="title" value="' . $title->getPrefixedText() . '"/>' .
		       wfMsg( 'smw_browse_article' ) . "<br />\n" .
		       '    <input type="text" name="article" value="' . htmlspecialchars( $this->articletext ) . '" />' . "\n" .
		       '    <input type="submit" value="' . wfMsg( 'smw_browse_go' ) . "\"/>\n" .
		       "  </form>\n";
	}

	/**
	 * Replace the last two space characters with unbreakable spaces for beautification.
	 *
	 * @param[in] $text string  Text to be transformed. Does not need to have spaces
	 * @return string  Transformed text
	 */
	private function unbreak( $text ) {
 		$text = preg_replace( '/[\s]/u', '&#160;', $text, - 1, $count );
 		return $count > 2 ? preg_replace( '/(&#160;)/u', ' ', $text, max( 0, $count - 2 ) ):$text;
	}

}
