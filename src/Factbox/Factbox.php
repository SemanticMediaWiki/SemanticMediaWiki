<?php

namespace SMW\Factbox;

use Html;
use Sanitizer;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\HtmlTableRenderer;
use SMW\MediaWiki\MessageBuilder;
use SMW\ParserData;
use SMW\Profiler;
use SMW\SemanticData;
use SMW\Store;
use SMWInfolink;
use SMWSemanticData;
use SMW\Localizer;

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
	 * @var HtmlTableRenderer
	 */
	private $htmlTableRenderer;

	/**
	 * @var MessageBuilder
	 */
	private $messageBuilder;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

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
	private $useInPreview = false;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param ParserData $parserData
	 * @param MessageBuilder $messageBuilder
	 */
	public function __construct( Store $store, ParserData $parserData, MessageBuilder $messageBuilder ) {
		$this->store = $store;
		$this->parserData = $parserData;
		$this->messageBuilder = $messageBuilder;
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @note contains information about wpPreview
	 *
	 * @since 2.1
	 *
	 * @param boolean
	 */
	public function useInPreview( $preview ) {
		$this->useInPreview = $preview;
	}

	/**
	 * Builds content suitable for rendering a Factbox and
	 * updating the ParserOuput accordingly
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
			$mws = $smwMagicWords === null ? array() : $smwMagicWords;
		} else {
			// @codeCoverageIgnoreStart
			$mws = isset( $parserOutput->mSMWMagicWords ) ? $parserOutput->mSMWMagicWords : array();
			// @codeCoverageIgnoreEnd
		}

		if ( in_array( 'SMW_SHOWFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_NONEMPTY;
		} elseif ( in_array( 'SMW_NOFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_HIDDEN;
		} elseif ( $this->useInPreview ) {
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
		return array(
			'ext.smw.style'
		);
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
	 * Ensure that the SemanticData container is really empty and not filled
	 * with a single "pseudo" property that obscures from re-reading the data
	 *
	 * MW's internal Parser does iterate the ParserOuput object several times
	 * which can leave a '_SKEY' property while in fact the the container is
	 * empty.
	 *
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return boolean
	 */
	protected function isEmpty( SemanticData $semanticData ) {

		$property = new DIProperty( '_SKEY' );

		foreach( $semanticData->getPropertyValues( $property ) as $dataItem ) {
			$semanticData->removePropertyObjectValue( $property, $dataItem );
		}

		return $semanticData->isEmpty();
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

		$this->htmlTableRenderer = $this->applicationFactory->newMwCollaboratorFactory()->newHtmlTableRenderer();

		$text = '';

		// Hook deprecated with SMW 1.9 and will vanish with SMW 1.11
		wfRunHooks( 'smwShowFactbox', array( &$text, $semanticData ) );

		// Hook since 1.9
		if ( wfRunHooks( 'SMW::Factbox::BeforeContentGeneration', array( &$text, $semanticData ) ) ) {

			$this->getTableHeader( $semanticData->getSubject() );
			$this->getTableContent( $semanticData );

			$text .= Html::rawElement( 'div',
				array( 'class' => 'smwfact' ),
				$this->htmlTableRenderer->getHeaderItems() .
				$this->htmlTableRenderer->getHtml( array( 'class' => 'smwfacttable' ) )
			);
		}

		return $text;
	}

	/**
	 * Renders a table header for a given subject
	 *
	 * @since 1.9
	 *
	 * @param DIWikiPage $subject
	 */
	protected function getTableHeader( DIWikiPage $subject ) {

		$dataValue = $this->dataValueFactory->newDataItemValue( $subject, null );

		$browselink = SMWInfolink::newBrowsingLink(
			$dataValue->getText(),
			$dataValue->getWikiValue(),
			'swmfactboxheadbrowse'
		);

		$this->htmlTableRenderer->addHeaderItem( 'div',
			$this->messageBuilder->getMessage( 'smw_factbox_head', $browselink->getWikiText() )->text(),
			array( 'class' => 'smwfactboxhead' )
		);

		$rdflink = SMWInfolink::newInternalLink(
			$this->messageBuilder->getMessage( 'smw_viewasrdf' )->text(),
			Localizer::getInstance()->getNamespaceTextById( NS_SPECIAL ) . ':ExportRDF/' . $dataValue->getWikiValue(),
			'rdflink'
		);

		$this->htmlTableRenderer->addHeaderItem( 'div',
			$rdflink->getWikiText(),
			array( 'class' => 'smwrdflink' )
		);
	}

	/**
	 * Renders table content for a given SMWSemanticData object
	 *
	 * @since 1.9
	 *
	 * @param SMWSemanticData $semanticData
	 */
	protected function getTableContent( SemanticData $semanticData ) {

		// Do exclude some tags from processing otherwise the display
		// can become distorted due to unresolved/open tags (see Bug 23185)
		$excluded = array( 'table', 'tr', 'th', 'td', 'dl', 'dd', 'ul', 'li', 'ol', 'b', 'sup', 'sub' );
		$attributes = array();

		foreach ( $semanticData->getProperties() as $propertyDi ) {
			$propertyDv = $this->dataValueFactory->newDataItemValue( $propertyDi, null );

			if ( !$propertyDi->isShown() ) {
				// showing this is not desired, hide
				continue;
			} elseif ( $propertyDi->isUserDefined() ) {
				// User defined property (@note the preg_replace is a slight
				// hack to ensure that the left column does not get too narrow)
				$propertyDv->setCaption( preg_replace( '/[ ]/u', '&#160;', $propertyDv->getWikiValue(), 2 ) );
				$attributes['property'] = array( 'class' => 'smwpropname' );
				$attributes['values'] = array( 'class' => 'smwprops' );
			} elseif ( $propertyDv->isVisible() ) {
				// Predefined property
				$attributes['property'] = array( 'class' => 'smwspecname' );
				$attributes['values'] = array( 'class' => 'smwspecs' );
			} else {
				// predefined, internal property
				// @codeCoverageIgnoreStart
				continue;
				// @codeCoverageIgnoreEnd
			}

			$valuesHtml = array();
			foreach ( $semanticData->getPropertyValues( $propertyDi ) as $dataItem ) {

				$dataValue = $this->dataValueFactory->newDataItemValue( $dataItem, $propertyDi );
				$dataValue->setServiceLinksRenderState( false );

				if ( $dataValue->isValid() ) {
					$valuesHtml[] = Sanitizer::removeHTMLtags(
						$dataValue->getLongWikiText( true ), null, array(), array(), $excluded
						) . $dataValue->getInfolinkText( SMW_OUTPUT_WIKI );
				}
			}

			// Invoke table content
			$this->htmlTableRenderer->addCell(
				$propertyDv->getShortWikiText( true ),
				$attributes['property']
			);

			$this->htmlTableRenderer->addCell(
				$this->messageBuilder->listToCommaSeparatedText( $valuesHtml ),
				$attributes['values']
			);

			$this->htmlTableRenderer->addRow();
		}
	}

}
