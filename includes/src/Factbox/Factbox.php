<?php

namespace SMW;

use SMWInfolink;
use SMWOutputs;

use IContextSource;
use ParserOutput;
use Sanitizer;
use Title;
use Html;

/**
 * Class handling the "Factbox" content rendering
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class Factbox {

	/** @var Store */
	protected $store;

	/** @var ParserData */
	protected $parserData;

	/** @var Settings */
	protected $settings;

	/** @var TableFormatter */
	protected $tableFormatter;

	/** @var IContextSource */
	protected $context;

	/** @var boolean */
	protected $isVisible = false;

	/** @var string */
	protected $content = null;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param IParserData $parserData
	 * @param Settings $settings
	 * @param IContextSource $context
	 */
	public function __construct(
		Store $store,
		ParserData $parserData,
		Settings $settings,
		IContextSource $context
	) {
		$this->store = $store;
		$this->parserData = $parserData;
		$this->settings = $settings;
		$this->context = $context;
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
			$this->parserData->updateOutput();
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
		} elseif ( $this->context->getRequest()->getCheck( 'wpPreview' ) ) {
			$showfactbox = $this->settings->get( 'smwgShowFactboxEdit' );
		} else {
			$showfactbox = $this->settings->get( 'smwgShowFactbox' );
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

		$semanticData = $this->parserData->getData();

		if ( $semanticData === null || $semanticData->stubObject || $this->isEmpty( $semanticData ) ) {
			$semanticData = $this->store->getSemanticData( $this->parserData->getSubject() );
		}

		if ( $showFactbox === SMW_FACTBOX_SPECIAL && !$semanticData->hasVisibleSpecialProperties() ) {
			// show only if there are special properties
			return '';
		} else if ( $showFactbox === SMW_FACTBOX_NONEMPTY && !$semanticData->hasVisibleProperties() ) {
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
		Profiler::In( __METHOD__ );

		$this->tableFormatter = new TableFormatter();
		$text = '';

		// Hook deprecated with SMW 1.9 and will vanish with SMW 1.11
		wfRunHooks( 'smwShowFactbox', array( &$text, $semanticData ) );

		// Hook since 1.9
		if ( wfRunHooks( 'SMW::Factbox::showContent', array( &$text, $semanticData ) ) ) {

			$this->getTableHeader( $semanticData->getSubject() );
			$this->getTableContent( $semanticData );

			$text .= Html::rawElement( 'div',
				array( 'class' => 'smwfact' ),
				$this->tableFormatter->getHeaderItems() .
				$this->tableFormatter->getTable( array( 'class' => 'smwfacttable' ) )
			);
		}

		Profiler::Out( __METHOD__ );
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

		$dataValue = DataValueFactory::getInstance()->newDataItemValue( $subject, null );

		$browselink = SMWInfolink::newBrowsingLink(
			$dataValue->getText(),
			$dataValue->getWikiValue(),
			'swmfactboxheadbrowse'
		);

		$this->tableFormatter->addHeaderItem( 'span',
			$this->context->msg( 'smw_factbox_head', $browselink->getWikiText() )->inContentLanguage()->text(),
			array( 'class' => 'smwfactboxhead' )
		);

		$rdflink = SMWInfolink::newInternalLink(
			$this->context->msg( 'smw_viewasrdf' )->inContentLanguage()->text(),
			$subject->getTitle()->getPageLanguage()->getNsText( NS_SPECIAL ) . ':ExportRDF/' . $dataValue->getWikiValue(),
			'rdflink'
		);

		$this->tableFormatter->addHeaderItem( 'span',
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
		Profiler::In( __METHOD__ );

		// Do exclude some tags from processing otherwise the display
		// can become distorted due to unresolved/open tags (see Bug 23185)
		$excluded = array( 'table', 'tr', 'th', 'td', 'dl', 'dd', 'ul', 'li', 'ol', 'b', 'sup', 'sub' );
		$attributes = array();

		foreach ( $semanticData->getProperties() as $propertyDi ) {
			$propertyDv = DataValueFactory::getInstance()->newDataItemValue( $propertyDi, null );

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

				$dataValue = DataValueFactory::getInstance()->newDataItemValue( $dataItem, $propertyDi );

				if ( $dataValue->isValid() ) {
					$valuesHtml[] = Sanitizer::removeHTMLtags(
						$dataValue->getLongWikiText( true ) , null, array(), array(), $excluded
						) . $dataValue->getInfolinkText( SMW_OUTPUT_WIKI );
				}
			}

			// Invoke table content
			$this->tableFormatter->addTableCell(
				$propertyDv->getShortWikiText( true ),
				$attributes['property']
			);

			$this->tableFormatter->addTableCell(
				$this->context->getLanguage()->listToText( $valuesHtml ),
				$attributes['values']
			);

			$this->tableFormatter->addTableRow();
		}

		Profiler::Out( __METHOD__ );
	}
}
