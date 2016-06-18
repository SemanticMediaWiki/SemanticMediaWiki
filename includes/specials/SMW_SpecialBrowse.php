<?php

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\SemanticData;
use SMW\UrlEncoder;

/**
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
		if ( $smwgBrowseShowAll ) {
			self::$incomingvaluescount = 21;
			self::$incomingpropertiescount = - 1;
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

		// @see SMWInfolink::encodeParameters
		if ( $query === null && $this->getRequest()->getCheck( 'x' ) ) {
			$query = $this->getRequest()->getVal( 'x' );
		}

		// no GET parameters? Then try the URL
		if ( is_null( $this->articletext ) ) {
			$this->articletext = UrlEncoder::decode( $query );
		}

		$this->subject = DataValueFactory::getInstance()->newTypeIDValue( '_wpg', $this->articletext );
		$offsettext = $wgRequest->getVal( 'offset' );
		$this->offset = ( is_null( $offsettext ) ) ? 0 : intval( $offsettext );

		$dir = $wgRequest->getVal( 'dir' );

		if ( $smwgBrowseShowAll ) {
			$this->showoutgoing = true;
			$this->showincoming = true;
		}

		if ( $dir === 'both' || $dir === 'in' ) {
			$this->showincoming = true;
		}

		if ( $dir === 'in' ) {
			$this->showoutgoing = false;
		}

		if ( $dir === 'out' ) {
			$this->showincoming = false;
		}

		$out = $this->getOutput();

		$out->addModuleStyles( array(
			'mediawiki.ui',
			'mediawiki.ui.button',
			'mediawiki.ui.input',
		) );

		$out->addHTML( $this->displayBrowse() );
		$this->addExternalHelpLinks();

		SMWOutputs::commitToOutputPage( $out ); // make sure locally collected output data is pushed to the output!
	}

	/**
	 * Create and output HTML including the complete factbox, based on the extracted
	 * parameters in the execute comment.
	 *
	 * @return string  A HTML string with the factbox
	 */
	private function displayBrowse() {
		global $wgContLang;
		$html = "\n";
		$leftside = !( $wgContLang->isRTL() ); // For right to left languages, all is mirrored
		$modules = array();

		if ( $this->subject->isValid() ) {

			$semanticData = new SemanticData( $this->subject->getDataItem() );
			$store = ApplicationFactory::getInstance()->getStore();

			$html .= $this->displayHead();

			if ( $this->showoutgoing ) {
				$semanticData = $store->getSemanticData( $this->subject->getDataItem() );
				$html .= $this->displayData( $semanticData, $leftside );
				$html .= $this->displayCenter();
			}

			if ( $this->showincoming ) {
				list( $indata, $more ) = $this->getInData();
				global $smwgBrowseShowInverse;

				if ( !$smwgBrowseShowInverse ) {
					$leftside = !$leftside;
				}

				$html .= $this->displayData( $indata, $leftside, true );
				$html .= $this->displayBottom( $more );
			}

			$this->articletext = $this->subject->getWikiValue();

			\Hooks::run( 'SMW::Browse::AfterDataLookupComplete', array( $store, $semanticData, &$html, &$modules ) );
			$this->getOutput()->addModules( $modules );

			// Add a bit space between the factbox and the query form
			if ( !$this->including() ) {
				$html .= '<div style="margin-top:15px;"></div>' ."\n";
			}
		}

		if ( !$this->including() ) {
			$html .= $this->queryForm();
		}

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Creates the HTML table displaying the data of one subject.
	 *
	 * @param[in] $data SMWSemanticData  The data to be displayed
	 * @param[in] $left bool  Should properties be displayed on the left side?
	 * @param[in] $incoming bool  Is this an incoming? Or an outgoing?
	 *
	 * @return string A string containing the HTML with the factbox
	 */
	private function displayData( SMWSemanticData $data, $left = true, $incoming = false ) {
		// Some of the CSS classes are different for the left or the right side.
		// In this case, there is an "i" after the "smwb-". This is set here.
		$ccsPrefix = $left ? 'smwb-' : 'smwb-i';

		$html = "<table class=\"{$ccsPrefix}factbox\" cellpadding=\"0\" cellspacing=\"0\">\n";

		$diProperties = $data->getProperties();
		$noresult = true;
		foreach ( $diProperties as $key => $diProperty ) {
			$dvProperty = DataValueFactory::getInstance()->newDataValueByItem( $diProperty, null );

			if ( $dvProperty->isVisible() ) {
				$dvProperty->setCaption( $this->getPropertyLabel( $dvProperty, $incoming ) );
				$proptext = $dvProperty->getShortHTMLText( smwfGetLinker() ) . "\n";
			} elseif ( $diProperty->getKey() == '_INST' ) {
				$proptext = smwfGetLinker()->specialLink( 'Categories' );
			} elseif ( $diProperty->getKey() == '_REDI' ) {
				$proptext = smwfGetLinker()->specialLink( 'Listredirects', 'isredirect' );
			} else {
				continue; // skip this line
			}

			$head  = '<th>' . $proptext . "</th>\n";

			$body  = "<td>\n";

			$values = $data->getPropertyValues( $diProperty );

			if ( $incoming && ( count( $values ) >= self::$incomingvaluescount ) ) {
				$moreIncoming = true;
				array_pop( $values );
			} else {
				$moreIncoming = false;
			}

			$first = true;
			foreach ( $values as /* SMWDataItem */ $di ) {
				if ( $first ) {
					$first = false;
				} else {
					$body .= ', ';
				}

				if ( $incoming ) {
					$dv = DataValueFactory::getInstance()->newDataValueByItem( $di, null );
				} else {
					$dv = DataValueFactory::getInstance()->newDataValueByItem( $di, $diProperty );
				}

				$body .= "<span class=\"{$ccsPrefix}value\">" .
				         $this->displayValue( $dvProperty, $dv, $incoming ) . "</span>\n";
			}

			// Added in 2.3
			// link to the remaining incoming pages
			if ( $moreIncoming && \Hooks::run( 'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate', array( $diProperty, $this->subject->getDataItem(), &$body ) ) ) {
				$body .= Html::element(
					'a',
					array(
						'href' => SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( array(
							 'property' => $dvProperty->getWikiValue(),
							 'value' => $this->subject->getWikiValue()
						) )
					),
					wfMessage( 'smw_browse_more' )->text()
				);

			}

			$body .= "</td>\n";

			// display row
			$html .= "<tr class=\"{$ccsPrefix}propvalue\">\n" .
					( $left ? ( $head . $body ):( $body . $head ) ) . "</tr>\n";
			$noresult = false;
		} // end foreach properties

		if ( $noresult ) {
			$html .= "<tr class=\"smwb-propvalue\"><th> &#160; </th><td><em>" .
				wfMessage( $incoming ? 'smw_browse_no_incoming':'smw_browse_no_outgoing' )->text() . "</em></td></tr>\n";
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
	 *
	 * @return string  HTML with the link to the article, browse, and search pages
	 */
	private function displayValue( SMWPropertyValue $property, SMWDataValue $dataValue, $incoming ) {
		$linker = smwfGetLinker();

		// Allow the DV formatter to access a specific language code
		$dataValue->setOption(
			'content.language',
			Localizer::getInstance()->getPreferredContentLanguage( $this->subject->getDataItem() )->getCode()
		);

		$dataValue->setOption(
			'user.language',
			Localizer::getInstance()->getUserLanguage()->getCode()
		);

		$dataValue->setContextPage(
			$this->subject->getDataItem()
		);

		// Use LOCL formatting where appropriate (date)
		$dataValue->setOutputFormat( 'LOCL' );

		$html = $dataValue->getLongHTMLText( $linker );

		if ( $dataValue->getTypeID() === '_wpg' || $dataValue->getTypeID() === '__sob' ) {
			$html .= "&#160;" . SMWInfolink::newBrowsingLink( '+', $dataValue->getLongWikiText() )->getHTML( $linker );
		} elseif ( $incoming && $property->isVisible() ) {
			$html .= "&#160;" . SMWInfolink::newInversePropertySearchLink( '+', $dataValue->getTitle(), $property->getDataItem()->getLabel(), 'smwsearch' )->getHTML( $linker );
		} elseif ( $dataValue->getProperty() instanceof DIProperty && $dataValue->getProperty()->getKey() !== '_INST' ) {
			$html .= $dataValue->getInfolinkText( SMW_OUTPUT_HTML, $linker );
		}

		return $html;
	}

	/**
	 * Displays the subject that is currently being browsed to.
	 *
	 * @return string A string containing the HTML with the subject line
	 */
	private function displayHead() {
		global $wgOut;

		$wgOut->setHTMLTitle( $this->subject->getTitle() );
		$html = "<table class=\"smwb-factbox\" cellpadding=\"0\" cellspacing=\"0\">\n" .
			"<tr class=\"smwb-title\"><td colspan=\"2\">\n" .
			$this->subject->getLongHTMLText( smwfGetLinker() ) . "\n" .
			"</td></tr>\n</table>\n";

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
			     $this->linkHere( wfMessage( 'smw_browse_hide_incoming' )->text(), true, false, 0 ):
		         $this->linkHere( wfMessage( 'smw_browse_show_incoming' )->text(), true, true, $this->offset ) ) .
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
		global $smwgBrowseShowAll;
		if ( !$smwgBrowseShowAll ) {
			if ( ( $this->offset > 0 ) || $more ) {
				$offset = max( $this->offset - self::$incomingpropertiescount + 1, 0 );
				$html .= ( $this->offset == 0 ) ? wfMessage( 'smw_result_prev' )->text():
					     $this->linkHere( wfMessage( 'smw_result_prev' )->text(), $this->showoutgoing, true, $offset );
				$offset = $this->offset + self::$incomingpropertiescount - 1;
				// @todo FIXME: i18n patchwork.
				$html .= " &#160;&#160;&#160;  <strong>" . wfMessage( 'smw_result_results' )->text() . " " . ( $this->offset + 1 ) .
						 " – " . ( $offset ) . "</strong>  &#160;&#160;&#160; ";
				$html .= $more ? $this->linkHere( wfMessage( 'smw_result_next' )->text(), $this->showoutgoing, true, $offset ):wfMessage( 'smw_result_next' )->text();
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
	 *
	 * @return string  HTML with the link to this page
	 */
	private function linkHere( $text, $out, $in, $offset ) {
		$frag = ( $text == wfMessage( 'smw_browse_show_incoming' )->text() ) ? '#smw_browse_incoming' : '';

		return Html::element(
			'a',
			array(
				'href' => SpecialPage::getSafeTitleFor( 'Browse' )->getLocalURL( array(
					'offset' => $offset,
					'dir' => $out ? ( $in ? 'both' : 'out' ) : 'in',
					'article' => $this->subject->getLongWikiText()
				) ) . $frag
			),
			$text
		);
	}

	/**
	 * Creates a Semantic Data object with the incoming properties instead of the
	 * usual outproperties.
	 *
	 * @return array(SMWSemanticData, bool)  The semantic data including all inproperties, and if there are more inproperties left
	 */
	private function getInData() {
		$indata = new SMWSemanticData( $this->subject->getDataItem() );
		$options = new SMWRequestOptions();
		$options->sort = true;
		$options->limit = self::$incomingpropertiescount;
		if ( $this->offset > 0 ) {
			$options->offset = $this->offset;
		}

		$store = ApplicationFactory::getInstance()->getStore();
		$inproperties = $store->getInProperties( $this->subject->getDataItem(), $options );

		if ( count( $inproperties ) == self::$incomingpropertiescount ) {
			$more = true;
			array_pop( $inproperties ); // drop the last one
		} else {
			$more = false;
		}

		$valoptions = new SMWRequestOptions();
		$valoptions->sort = true;
		$valoptions->limit = self::$incomingvaluescount;

		foreach ( $inproperties as $property ) {
			$values = $store->getPropertySubjects( $property, $this->subject->getDataItem(), $valoptions );
			foreach ( $values as $value ) {
				$indata->addPropertyObjectValue( $property, $value );
			}
		}

		// Added in 2.3
		\Hooks::run( 'SMW::Browse::AfterIncomingPropertiesLookupComplete', array( $store, $indata, $valoptions ) );

		return array( $indata, $more );
	}

	/**
	 * Figures out the label of the property to be used. For outgoing ones it is just
	 * the text, for incoming ones we try to figure out the inverse one if needed,
	 * either by looking for an explicitly stated one or by creating a default one.
	 *
	 * @param[in] $property SMWPropertyValue  The property of interest
	 * @param[in] $incoming bool  If it is an incoming property
	 *
	 * @return string  The label of the property
	 */
	private function getPropertyLabel( SMWPropertyValue $property, $incoming = false ) {
		global $smwgBrowseShowInverse;

		if ( $incoming && $smwgBrowseShowInverse ) {
			$oppositeprop = SMWPropertyValue::makeUserProperty( wfMessage( 'smw_inverse_label_property' )->text() );
			$labelarray = ApplicationFactory::getInstance()->getStore()->getPropertyValues( $property->getDataItem()->getDiWikiPage(), $oppositeprop->getDataItem() );
			$rv = ( count( $labelarray ) > 0 ) ? $labelarray[0]->getLongWikiText():
				wfMessage( 'smw_inverse_label_default', $property->getWikiValue() )->text();
		} else {
			$rv = $property->getWikiValue();
		}

		return $this->unbreak( $rv );
	}

	/**
	 * Creates the query form in order to quickly switch to a specific article.
	 *
	 * @return string A string containing the HTML for the form
	 */
	private function queryForm() {

		if ( $this->getRequest()->getVal( 'printable' ) === 'yes' ) {
			return '';
		}

		SMWOutputs::requireResource( 'ext.smw.browse' );
		$title = SpecialPage::getTitleFor( 'Browse' );
		return '  <form name="smwbrowse" action="' . htmlspecialchars( $title->getLocalURL() ) . '" method="get">' . "\n" .
			'    <input type="hidden" name="title" value="' . $title->getPrefixedText() . '"/>' .
			wfMessage( 'smw_browse_article' )->text() . "<br />\n" .
		    ' <div class="browse-input-resp"> <div class="input-field"><input type="text" name="article" size="40" id="page_input_box" class="input mw-ui-input" value="' . htmlspecialchars( $this->articletext ) . '" /></div>' .
		    ' <div class="button-field"><input type="submit" class="input-button mw-ui-button" value="' . wfMessage( 'smw_browse_go' )->text() . "\"/></div></div>\n" .
		    "  </form>\n";
	}

	/**
	 * Replace the last two space characters with unbreakable spaces for beautification.
	 *
	 * @param[in] $text string  Text to be transformed. Does not need to have spaces
	 * @return string  Transformed text
	 */
	private function unbreak( $text ) {
		$nonBreakingSpace = html_entity_decode( '&#160;', ENT_NOQUOTES, 'UTF-8' );
		$text = preg_replace( '/[\s]/u', $nonBreakingSpace, $text, - 1, $count );
		return $count > 2 ? preg_replace( '/($nonBreakingSpace)/u', ' ', $text, max( 0, $count - 2 ) ):$text;
	}

	/**
	 * FIXME MW 1.25
	 */
	private function addExternalHelpLinks() {

		if ( !method_exists( $this, 'addHelpLink' ) || ( $this->getRequest()->getVal( 'printable' ) === 'yes' ) ) {
			return null;
		}

		if ( $this->subject->isValid() ) {
			$link = SpecialPage::getTitleFor( 'ExportRDF', $this->subject->getTitle()->getPrefixedText() )->getLocalUrl( 'syntax=rdf' );

			$this->getOutput()->setIndicators( array(
				Html::rawElement(
					'div',
					array(
						'class' => 'mw-indicator smw-page-indicator-rdflink'
					),
					Html::rawElement(
						'a',
						array(
							'href' => $link
					), 'RDF' )
				)
			) );
		}

		$this->addHelpLink( wfMessage( 'smw-specials-browse-helplink' )->escaped(), true );
	}

	protected function getGroupName() {
		return 'smw_group';
	}
}
