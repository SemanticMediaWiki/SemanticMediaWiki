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
use SMW\Message;
use SMWDataValue as DataValue;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\RequestOptions;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author Denny Vrandecic
 * @author mwjames
 */
class ContentsBuilder {

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
	public function importOptionsFromJson( $json ) {
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
		$html .= $this->displayData( $semanticData, $leftside, false, true );
		$html .= $this->displayCenter( $this->subject->getLongWikiText() );
		$html .= $this->displayData( $semanticData, $leftside, true, true );
		$html .= $this->displayBottom( false );

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$html .= FormHelper::getQueryForm( $this->articletext );
		}

		return $html;
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

		if ( !$this->subject->isValid() ) {
			return $html;
		}

		$semanticData = new SemanticData( $this->subject->getDataItem() );
		$html .= $this->displayHead();

		if ( $this->showoutgoing ) {
			$semanticData = $this->store->getSemanticData( $this->subject->getDataItem() );
			$html .= $this->displayData( $semanticData, $leftside );
			$html .= $this->displayCenter( $this->subject->getLongWikiText() );
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

		if ( $this->getOption( 'printable' ) !== 'yes' && !$this->getOption( 'including' ) ) {
			$html .= FormHelper::getQueryForm( $this->articletext );
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
	private function displayData( SemanticData $data, $left = true, $incoming = false, $isLoading = false ) {
		// Some of the CSS classes are different for the left or the right side.
		// In this case, there is an "i" after the "smwb-". This is set here.
		$ccsPrefix = $left ? 'smwb-' : 'smwb-i';

		$html = "<table class=\"{$ccsPrefix}factbox\" cellpadding=\"0\" cellspacing=\"0\">\n";
		$noresult = true;

		$contextPage = $data->getSubject();
		$diProperties = $data->getProperties();
		$showInverse = $this->getOption( 'showInverse' );

		foreach ( $diProperties as $key => $diProperty ) {

			$dvProperty = DataValueFactory::getInstance()->newDataValueByItem(
				$diProperty,
				null
			);

			$dvProperty->setContextPage(
				$contextPage
			);

			$propertyLabel = ValueFormatter::getPropertyLabel(
				$dvProperty,
				$incoming,
				$showInverse
			);

			if ( $propertyLabel === null ) {
				continue;
			}

			$head  = '<th>' . $propertyLabel . "</th>\n";
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
			$noMsgKey = $incoming ? 'smw_browse_no_incoming':'smw_browse_no_outgoing';

			$html .= "<tr class=\"smwb-propvalue\"><th> &#160; </th><td><em>" .
				wfMessage( $isLoading ? 'smw-browse-from-backend' : $noMsgKey )->escaped() . "</em></td></tr>\n";
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

		$dataValue->setContextPage(
			$this->subject->getDataItem()
		);

		return ValueFormatter::getFormattedValue( $dataValue, $property, $incoming );
	}

	/**
	 * Displays the subject that is currently being browsed to.
	 *
	 * @return string A string containing the HTML with the subject line
	 */
	private function displayHead() {

		if ( $this->subject->getDataItem()->getNamespace() === SMW_NS_PROPERTY ) {
			$caption = '';

			$dv = DataValueFactory::getInstance()->newDataValueByItem(
				DIProperty::newFromUserLabel( $this->subject->getDataItem()->getDBKey() )
			);

			$this->subject->setCaption( $dv->getFormattedLabel( DataValueFormatter::WIKI_LONG ) );
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
	private function displayCenter( $article ) {

		if ( $this->showincoming ) {
			$parameters = array(
				'offset'  => 0,
				'dir'     => 'out',
				'article' => $article
			);

			$linkMsg = 'smw_browse_hide_incoming';
		} else {
			$parameters = array(
				'offset'  => $this->offset,
				'dir'     => 'both',
				'article' => $article
			);

			$linkMsg = 'smw_browse_show_incoming';
		}

		$html = FormHelper::createLink( $linkMsg, $parameters );

		return "<a name=\"smw_browse_incoming\"></a>\n" .
		       "<table class=\"smwb-factbox\" cellpadding=\"0\" cellspacing=\"0\">\n" .
		       "<tr class=\"smwb-center\"><td colspan=\"2\">\n" . $html .
		       "&#160;\n" . "</td></tr>\n" . "</table>\n";
	}

	/**
	 * Creates the HTML for the bottom bar including the links with further navigation options.
	 *
	 * @param[in] $more bool  Are there more inproperties to be displayed?
	 * @return string  HTMl with the bottom bar
	 */
	private function displayBottom( $more ) {

		$article = $this->subject->getLongWikiText();

		$open  = "<table class=\"smwb-factbox\" cellpadding=\"0\" cellspacing=\"0\">\n" .
		         "<tr class=\"smwb-center\"><td colspan=\"2\">\n";

		$close = "&#160;\n" . "</td></tr>\n" . "</table>\n";
		$html = '';

		if ( $this->getOption( 'showAll' ) ) {
			return $open . $close;
		}

		if ( ( $this->offset > 0 ) || $more ) {
			$offset = max( $this->offset - $this->incomingPropertiesCount + 1, 0 );

			$parameters = array(
				'offset'  => $offset,
				'dir'     => $this->showoutgoing ? 'both' : 'in',
				'article' => $article
			);

			$linkMsg = 'smw_result_prev';

			$html .= ( $this->offset == 0 ) ? wfMessage( $linkMsg )->escaped() : FormHelper::createLink( $linkMsg, $parameters );

			$offset = $this->offset + $this->incomingPropertiesCount - 1;

			$parameters = array(
				'offset'  => $offset,
				'dir'     => $this->showoutgoing ? 'both' : 'in',
				'article' => $article
			);

			$linkMsg = 'smw_result_next';

			// @todo FIXME: i18n patchwork.
			$html .= " &#160;&#160;&#160;  <strong>" . wfMessage( 'smw_result_results' )->escaped() . " " . ( $this->offset + 1 ) .
					 " – " . ( $offset ) . "</strong>  &#160;&#160;&#160; ";
			$html .= $more ? FormHelper::createLink( $linkMsg, $parameters ) : wfMessage( $linkMsg )->escaped();
		}

		return $open . $html . $close;
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

}
