<?php

namespace SMW\Factbox;

use Html;
use Sanitizer;
use Title;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Message;
use SMW\ParserData;
use SMW\Profiler;
use SMW\SemanticData;
use SMW\Store;
use SMW\Utils\HtmlDivTable;
use SMW\Utils\HtmlTabs;
use SMWInfolink;
use SMWSemanticData;

/**
 * Class handling the "Factbox" content rendering
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class Factbox {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var integer
	 */
	private $featureSet = 0;

	/**
	 * @var boolean
	 */
	protected $isVisible = false;

	/**
	 * @var string
	 */
	protected $content = null;

	/**
	 * @var boolean
	 */
	private $previewFlag = false;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param ParserData $parserData
	 */
	public function __construct( Store $store, ParserData $parserData ) {
		$this->store = $store;
		$this->parserData = $parserData;
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $featureSet
	 */
	public function setFeatureSet( $featureSet ) {
		$this->featureSet = $featureSet;
	}

	/**
	 * @note contains information about wpPreview
	 *
	 * @since 2.1
	 *
	 * @param boolean $previewFlag
	 */
	public function setPreviewFlag( $previewFlag ) {
		$this->previewFlag = $previewFlag;
	}

	/**
	 * Builds content suitable for rendering a Factbox and
	 * updating the ParserOutput accordingly
	 *
	 * @since 1.9
	 *
	 * @return Factbox
	 */
	public function doBuild() {

		$this->content = $this->fetchContent( $this->getMagicWords() );

		if ( $this->content !== '' ) {
			$this->parserData->getOutput()->addModules( $this->getModules() );
			$this->parserData->pushSemanticDataToParserOutput();
			$this->isVisible = true;
		}

		return $this;
	}

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function getTitle() {
		return $this->parserData->getTitle();
	}

	/**
	 * Returns content
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Returns if content is visible
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isVisible() {
		return $this->isVisible;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $rendered
	 * @param string $derived
	 *
	 * @return string
	 */
	public static function tabs( $rendered, $derived = '' ) {

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setActiveTab( 'facts-rendered' );
		$htmlTabs->tab(
			'facts-rendered',
			Message::get( 'smw-factbox-facts' , Message::TEXT, Message::USER_LANGUAGE ),
			[
				'title' => Message::get( 'smw-factbox-facts-help' , Message::TEXT, Message::USER_LANGUAGE )
			]
		);

		$htmlTabs->content( 'facts-rendered', $rendered );

		$htmlTabs->tab(
			'facts-derived',
			Message::get( 'smw-factbox-derived' , Message::TEXT, Message::USER_LANGUAGE ),
			[
				'hide' => $derived === '' ? true : false
			]
		);

		$htmlTabs->content( 'facts-derived', $derived );

		return $htmlTabs->buildHTML(
			[
				'class' => 'smw-factbox'
			]
		);
	}

	/**
	 * Returns magic words attached to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	protected function getMagicWords() {

		$settings = $this->applicationFactory->getSettings();
		$parserOutput = $this->parserData->getOutput();

		// Prior MW 1.21 mSMWMagicWords is used (see SMW\ParserTextProcessor)
		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			$smwMagicWords = $parserOutput->getExtensionData( 'smwmagicwords' );
			$mws = $smwMagicWords === null ? [] : $smwMagicWords;
		} else {
			// @codeCoverageIgnoreStart
			$mws = isset( $parserOutput->mSMWMagicWords ) ? $parserOutput->mSMWMagicWords : [];
			// @codeCoverageIgnoreEnd
		}

		if ( in_array( 'SMW_SHOWFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_NONEMPTY;
		} elseif ( in_array( 'SMW_NOFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_HIDDEN;
		} elseif ( $this->previewFlag ) {
			$showfactbox = $settings->get( 'smwgShowFactboxEdit' );
		} else {
			$showfactbox = $settings->get( 'smwgShowFactbox' );
		}

		return $showfactbox;
	}

	/**
	 * Returns required resource modules
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	protected function getModules() {
		return [
			'ext.smw.style',
			'ext.smw.table.styles'
		];
	}

	/**
	 * Returns content found for a given ParserOutput object and if the required
	 * custom data was not available then semantic data are retrieved from
	 * the store for a given subject.
	 *
	 * The method checks whether the given setting of $showfactbox requires
	 * displaying the given data at all.
	 *
	 * @since 1.9
	 *
	 * @return integer $showFactbox
	 *
	 * @return string|null
	 */
	protected function fetchContent( $showFactbox = SMW_FACTBOX_NONEMPTY ) {

		if ( $showFactbox === SMW_FACTBOX_HIDDEN ) {
			return '';
		}

		$semanticData = $this->parserData->getSemanticData();

		if ( $semanticData === null || $semanticData->stubObject || $this->isEmpty( $semanticData ) ) {
			$semanticData = $this->store->getSemanticData( $this->parserData->getSubject() );
		}

		if ( $showFactbox === SMW_FACTBOX_SPECIAL && !$semanticData->hasVisibleSpecialProperties() ) {
			// show only if there are special properties
			return '';
		} elseif ( $showFactbox === SMW_FACTBOX_NONEMPTY && !$semanticData->hasVisibleProperties() ) {
			// show only if non-empty
			return '';
		}

		return $this->createTable( $semanticData );
	}

	/**
	 * Returns a formatted factbox table
	 *
	 * @since 1.9
	 *
	 * @param SMWSemanticData $semanticData
	 *
	 * @return string|null
	 */
	protected function createTable( SemanticData $semanticData ) {

		$html = '';

		// Hook deprecated with SMW 1.9 and will vanish with SMW 1.11
		\Hooks::run( 'smwShowFactbox', [ &$html, $semanticData ] );

		// Hook since 1.9
		if ( \Hooks::run( 'SMW::Factbox::BeforeContentGeneration', [ &$html, $semanticData ] ) ) {

			$header = $this->createHeader( $semanticData->getSubject() );
			$rows = $this->createRows( $semanticData );

			$html .= Html::rawElement(
				'div',
				[
					'class' => 'smwfact',
					'style' => 'display:block;'
				],
				$header . HtmlDivTable::table(
					$rows,
					[
						'class' => 'smwfacttable'
					]
				)
			);
		}

		return $html;
	}

	private function createHeader( DIWikiPage $subject ) {

		$dataValue = $this->dataValueFactory->newDataValueByItem( $subject, null );

		$browselink = SMWInfolink::newBrowsingLink(
			$dataValue->getPreferredCaption(),
			$dataValue->getWikiValue(),
			''
		);

		$header = Html::rawElement(
			'div',
			[ 'class' => 'smwfactboxhead' ],
			Message::get( [ 'smw-factbox-head', $browselink->getWikiText() ], Message::TEXT, Message::USER_LANGUAGE )
		);

		$rdflink = SMWInfolink::newInternalLink(
			Message::get( 'smw_viewasrdf', Message::TEXT, Message::USER_LANGUAGE ),
			Localizer::getInstance()->getNamespaceTextById( NS_SPECIAL ) . ':ExportRDF/' . $dataValue->getWikiValue(),
			'rdflink'
		);

		$header .= Html::rawElement(
			'div',
			[ 'class' => 'smwrdflink' ],
			$rdflink->getWikiText()
		);

		return $header;
	}

	private function createRows( SemanticData $semanticData ) {

		$rows = '';
		$attributes = [];

		$comma = Message::get(
			'comma-separator',
			Message::ESCAPED,
			Message::USER_LANGUAGE
		);

		$and = Message::get(
			'and',
			Message::ESCAPED,
			Message::USER_LANGUAGE
		);

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->getKey() === '_SOBJ' && !$this->hasFeature( SMW_FACTBOX_DISPLAY_SUBOBJECT ) ) {
				continue;
			}

			$propertyDv = $this->dataValueFactory->newDataValueByItem( $property, null );
			$row = '';

			if ( !$property->isShown() ) {
				// showing this is not desired, hide
				continue;
			} elseif ( $property->isUserDefined() ) {
				$propertyDv->setCaption( $propertyDv->getWikiValue() );
				$attributes['property'] = [ 'class' => 'smwpropname' ];
				$attributes['values'] = [ 'class' => 'smwprops' ];
			} elseif ( $propertyDv->isVisible() ) {
				// Predefined property
				$attributes['property'] = [ 'class' => 'smwspecname' ];
				$attributes['values'] = [ 'class' => 'smwspecs' ];
			} else {
				// predefined, internal property
				// @codeCoverageIgnoreStart
				continue;
				// @codeCoverageIgnoreEnd
			}

			$list = [];
			$html = '';

			foreach ( $semanticData->getPropertyValues( $property ) as $dataItem ) {

				$dataValue = $this->dataValueFactory->newDataValueByItem( $dataItem, $property );

				$outputFormat = $dataValue->getOutputFormat();
				$dataValue->setOutputFormat( $outputFormat ? $outputFormat : 'LOCL' );

				$dataValue->setOption( $dataValue::OPT_DISABLE_SERVICELINKS, true );

				if ( $dataValue->isValid() ) {
					$list[] = $dataValue->getLongWikiText( true ) . $dataValue->getInfolinkText( SMW_OUTPUT_WIKI );
				}
			}

			if ( $list !== [] ) {
				$last = array_pop( $list );

				if ( $list === [] ) {
					$html = $last;
				} else {
					$html = implode( $comma, $list ) . '&nbsp;' . $and . '&nbsp;' . $last;
				}
			}

			$row .= HtmlDivTable::cell(
				$propertyDv->getShortWikiText( true ),
				$attributes['property']
			);

			$row .= HtmlDivTable::cell(
				$html,
				$attributes['values']
			);

			$rows .= HtmlDivTable::row(
				$row
			);
		}

		return $rows;
	}

	private function isEmpty( SemanticData $semanticData ) {

		// MW's internal Parser does iterate the ParserOutput object several times
		// which can leave a '_SKEY' property while in fact the container is empty.
		$semanticData->removeProperty(
			new DIProperty( '_SKEY' )
		);

		return $semanticData->isEmpty();
	}


	private function hasFeature( $feature ) {
		return ( (int)$this->featureSet & $feature ) != 0;
	}

}
