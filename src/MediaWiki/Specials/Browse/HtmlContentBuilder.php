<?php

namespace SMW\MediaWiki\Specials\Browse;

use SMW\SemanticData;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\Localizer;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Store;
use Html;
use SMWDataValue as DataValue;
use SMW\RequestOptions;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author Denny Vrandecic
 * @author mwjames
 */
class HtmlContentBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @var boolean
	 */
	private $showoutgoing = true;

	/**
	 * To display incoming values?
	 *
	 * @var boolean
	 */
	private $showincoming = false;

	/**
	 * At which incoming property are we currently?
	 * @var integer
	 */
	private $offset = 0;

	/**
	 * How many incoming values should be asked for
	 * @var integer
	 */
	private $incomingValuesCount = 8;

	/**
	 * How many incoming properties should be asked for
	 * @var integer
	 */
	private $incomingPropertiesCount = 21;

	/**
	 * @var array
	 */
	private $extraModules = array();

	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param DIWikiPage $subject
	 */
	public function __construct( Store $store, DIWikiPage $subject ) {
		$this->store = $store;
		$this->subject = DataValueFactory::getInstance()->newDataValueByItem( $subject );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOption( $key ) {

		if ( isset( $this->options[$key] ) ) {
			return $this->options[$key];
		}

		return null;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $json
	 */
	public function setOptionsFromJsonFormat( $json ) {
		$this->options = json_decode( $json, true );
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getHtml() {

		if ( ( $offset = $this->getOption( 'offset' ) ) ) {
			$this->offset = $offset;
		}

		if ( $this->getOption( 'showAll' ) ) {
			$this->incomingValuesCount = 21;
			$this->incomingPropertiesCount = - 1;
			$this->showoutgoing = true;
			$this->showincoming = true;
		}

		if ( $this->getOption( 'dir' ) === 'both' || $this->getOption( 'dir' ) === 'in' ) {
			$this->showincoming = true;
		}

		if ( $this->getOption( 'dir' ) === 'in' ) {
			$this->showoutgoing = false;
		}

		if ( $this->getOption( 'dir' ) === 'out' ) {
			$this->showincoming = false;
		}

		return $this->doGenerateHtml();
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getEmptyHtml() {
		global $wgContLang;

		$leftside = !( $wgContLang->isRTL() );
		$html = "\n";

		$semanticData = new SemanticData( $this->subject->getDataItem() );
		$this->articletext = $this->subject->getWikiValue();

		$html .= $this->displayHead();
		$html .= $this->displayData( $semanticData, $leftside );
		$html .= $this->displayCenter();
		$html .= $this->displayData( $semanticData, $leftside, true );
		$html .= $this->displayBottom( false );

		if ( $this->getOption( 'printable' ) !== 'yes' ) {
			$html .= $this->queryForm( $this->articletext );
		}

		return $html;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public static function getPageSearchQuickForm( $articletext = '' ) {
		return "\n" . self::queryForm( $articletext );
	}

	/**
	 * Create and output HTML including the complete factbox, based on the extracted
	 * parameters in the execute comment.
	 *
	 * @return string  A HTML string with the factbox
	 */
	private function doGenerateHtml() {
		global $wgContLang;
		$html = "\n";
		$leftside = !( $wgContLang->isRTL() ); // For right to left languages, all is mirrored
		$modules = array();

		if ( $this->subject->isValid() ) {

			$semanticData = new SemanticData( $this->subject->getDataItem() );
			$html .= $this->displayHead();

			if ( $this->showoutgoing ) {
				$semanticData = $this->store->getSemanticData( $this->subject->getDataItem() );
				$html .= $this->displayData( $semanticData, $leftside );
				$html .= $this->displayCenter();
			}

			if ( $this->showincoming ) {
				list( $indata, $more ) = $this->getInData();

				if ( !$this->getOption( 'showInverse' ) ) {
					$leftside = !$leftside;
				}

				$html .= $this->displayData( $indata, $leftside, true );
				$html .= $this->displayBottom( $more );
			}

			$this->articletext = $this->subject->getWikiValue();

			\Hooks::run( 'SMW::Browse::AfterDataLookupComplete', array( $this->store, $semanticData, &$html, &$this->extraModules ) );
		}

		if ( $this->getOption( 'printable' ) !== 'yes' ) {
			$html .= $this->queryForm( $this->articletext );
		}

		$html .= Html::element(
			'div',
			array(
				'class' => 'smwb-modules',
				'data-modules' => json_encode( $this->extraModules )
			)
		);

		return $html;
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
	private function displayData( SemanticData $data, $left = true, $incoming = false ) {
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

			if ( $incoming && ( count( $values ) >= $this->incomingValuesCount ) ) {
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

				// For a redirect, disable the DisplayTitle to show the original (aka source) page
				if ( $diProperty->getKey() == '_REDI' ) {
					$dv->setOption( 'smwgDVFeatures', ( $dv->getOptionBy( 'smwgDVFeatures' ) & ~SMW_DV_WPV_DTITLE ) );
				}

				$body .= "<span class=\"{$ccsPrefix}value\">" .
				         $this->displayValue( $dvProperty, $dv, $incoming ) . "</span>\n";
			}

			// Added in 2.3
			// link to the remaining incoming pages
			if ( $moreIncoming && \Hooks::run( 'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate', array( $diProperty, $this->subject->getDataItem(), &$body ) ) ) {
				$body .= \Html::element(
					'a',
					array(
						'href' => \SpecialPage::getSafeTitleFor( 'SearchByProperty' )->getLocalURL( array(
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
	 * @param[in] $value DataValue  The actual value
	 * @param[in] $incoming bool  If this is an incoming or outgoing link
	 *
	 * @return string  HTML with the link to the article, browse, and search pages
	 */
	private function displayValue( \SMWPropertyValue $property, DataValue $dataValue, $incoming ) {
		$linker = smwfGetLinker();

		// Allow the DV formatter to access a specific language code
		$dataValue->setOption(
			DataValue::OPT_CONTENT_LANGUAGE,
			Localizer::getInstance()->getPreferredContentLanguage( $this->subject->getDataItem() )->getCode()
		);

		$dataValue->setOption(
			DataValue::OPT_USER_LANGUAGE,
			Localizer::getInstance()->getUserLanguage()->getCode()
		);

		$dataValue->setContextPage(
			$this->subject->getDataItem()
		);

		// Use LOCL formatting where appropriate (date)
		$dataValue->setOutputFormat( 'LOCL' );

		$html = $dataValue->getLongHTMLText( $linker );

		if ( $dataValue->getTypeID() === '_wpg' || $dataValue->getTypeID() === '__sob' ) {
			$html .= "&#160;" . \SMWInfolink::newBrowsingLink( '+', $dataValue->getLongWikiText() )->getHTML( $linker );
		} elseif ( $incoming && $property->isVisible() ) {
			$html .= "&#160;" . \SMWInfolink::newInversePropertySearchLink( '+', $dataValue->getTitle(), $property->getDataItem()->getLabel(), 'smwsearch' )->getHTML( $linker );
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

		if ( $this->subject->getDataItem()->getNamespace() === SMW_NS_PROPERTY ) {
			$property = \SMWDIProperty::newFromUserLabel( $this->subject->getDataItem()->getDBKey() );
			$caption = '';

			$title = $property->getCanonicalDiWikiPage()->getTitle();

			if ( ( $preferredLabel = $property->getPreferredLabel() ) !== '' && $title->getText() !== $preferredLabel ) {
				$caption = wfMessage( 'smw-property-preferred-title-format', $title->getPrefixedText(), $preferredLabel )->text();
			}

			$this->subject->setCaption( $caption );
		}

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

		if ( !$this->getOption( 'showAll' ) ) {
			if ( ( $this->offset > 0 ) || $more ) {
				$offset = max( $this->offset - $this->incomingPropertiesCount + 1, 0 );
				$html .= ( $this->offset == 0 ) ? wfMessage( 'smw_result_prev' )->text():
					     $this->linkHere( wfMessage( 'smw_result_prev' )->text(), $this->showoutgoing, true, $offset );
				$offset = $this->offset + $this->incomingPropertiesCount - 1;
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
				'href' => \SpecialPage::getSafeTitleFor( 'Browse' )->getLocalURL( array(
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
		$indata = new SemanticData( $this->subject->getDataItem() );

		$propRequestOptions = new RequestOptions();
		$propRequestOptions->sort = true;
		$propRequestOptions->limit = $this->incomingPropertiesCount;

		if ( $this->offset > 0 ) {
			$propRequestOptions->offset = $this->offset;
		}

		$incomingProperties = $this->store->getInProperties( $this->subject->getDataItem(), $propRequestOptions );
		$more = false;

		if ( count( $incomingProperties ) == $this->incomingPropertiesCount ) {
			$more = true;
			array_pop( $incomingProperties ); // drop the last one
		}

		$valRequestOptions = new RequestOptions();
		$valRequestOptions->sort = true;
		$valRequestOptions->limit = $this->incomingValuesCount;

		foreach ( $incomingProperties as $property ) {
			$values = $this->store->getPropertySubjects( $property, $this->subject->getDataItem(), $valRequestOptions );
			foreach ( $values as $value ) {
				$indata->addPropertyObjectValue( $property, $value );
			}
		}

		// Added in 2.3
		// Whether to show a more link or not can be set via
		// SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate
		\Hooks::run( 'SMW::Browse::AfterIncomingPropertiesLookupComplete', array( $this->store, $indata, $valRequestOptions ) );

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
	private function getPropertyLabel( \SMWPropertyValue $property, $incoming = false ) {

		if ( $incoming && $this->getOption( 'showInverse' ) ) {
			$oppositeprop = \SMWPropertyValue::makeUserProperty( wfMessage( 'smw_inverse_label_property' )->text() );
			$labelarray = $this->store->getPropertyValues( $property->getDataItem()->getDiWikiPage(), $oppositeprop->getDataItem() );
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
	private static function queryForm( $articletext ) {

		$title = \SpecialPage::getTitleFor( 'Browse' );

		$html = '<div style="margin-top:15px;"></div>' ."\n";
		$dir = $title->getPageLanguage()->isRTL() ? 'rtl' : 'ltr';

		$html .= '  <form name="smwbrowse" action="' . htmlspecialchars( $title->getLocalURL() ) . '" method="get">' . "\n" .
			'    <input type="hidden" name="title" value="' . $title->getPrefixedText() . '"/>' .
			wfMessage( 'smw_browse_article' )->text() . "<br />\n" .
		    ' <div class="browse-input-resp"> <div class="input-field"><input type="text"  dir="' . $dir . '" name="article" size="40" id="smwb-page-search" class="input mw-ui-input" value="' . htmlspecialchars( $articletext ) . '" /></div>' .
		    ' <div class="button-field"><input type="submit" class="input-button mw-ui-button" value="' . wfMessage( 'smw_browse_go' )->text() . "\"/></div></div>\n" .
		    "  </form>\n";

		return $html;
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

}
