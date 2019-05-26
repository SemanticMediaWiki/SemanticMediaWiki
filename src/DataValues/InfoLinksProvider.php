<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\Message;
use SMW\Parser\InTextAnnotationParser;
use SMW\PropertySpecificationLookup;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWInfolink as Infolink;
use SMWWikiPageValue as WikiPageValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class InfoLinksProvider {

	/**
	 * @var DataValue
	 */
	private $dataValue;

	/**
	 * @var PropertySpecificationLookup
	 */
	private $propertySpecificationLookup;

	/**
	 * @var Infolink[]
	 */
	protected $infoLinks = [];

	/**
	 * Used to control the addition of the standard search link.
	 * @var boolean
	 */
	private $hasSearchLink;

	/**
	 * Used to control service link creation.
	 * @var boolean
	 */
	private $hasServiceLinks;

	/**
	 * @var boolean
	 */
	private $enabledServiceLinks = true;

	/**
	 * @var boolean
	 */
	private $compactLink = false;

	/**
	 * @var boolean|array
	 */
	private $serviceLinkParameters = false;

	/**
	 * @var []
	 */
	private $disabledBrowseLinksByType = [ '_rec', '_mlt_rec', '_ref_rec' ];

	/**
	 * @var []
	 */
	private $disabledLinksByKey = [ '_ERRT' ];

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 * @param PropertySpecificationLookup $propertySpecificationLookup
	 */
	public function __construct( DataValue $dataValue, PropertySpecificationLookup $propertySpecificationLookup ) {
		$this->dataValue = $dataValue;
		$this->propertySpecificationLookup = $propertySpecificationLookup;
	}

	/**
	 * @since 2.4
	 */
	public function init() {
		$this->infoLinks = [];
		$this->hasSearchLink = false;
		$this->hasServiceLinks = false;
		$this->enabledServiceLinks = true;
		$this->serviceLinkParameters = false;
		$this->compactLink = false;
	}

	/**
	 * @since 2.4
	 */
	public function disableServiceLinks() {
		$this->enabledServiceLinks = false;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $compactLink
	 */
	public function setCompactLink( $compactLink ) {
		$this->compactLink = (bool)$compactLink;
	}

	/**
	 * Adds a single SMWInfolink object to the infoLinks array.
	 *
	 * @since 2.4
	 *
	 * @param Infolink $link
	 */
	public function addInfolink( Infolink $infoLink ) {
		$this->infoLinks[] = $infoLink;
	}

	/**
	 * @since 2.4
	 *
	 * @param array|false $serviceLinkParameters
	 */
	public function setServiceLinkParameters( $serviceLinkParameters ) {
		$this->serviceLinkParameters = $serviceLinkParameters;
	}

	/**
	 * Return an array of SMWLink objects that provide additional resources
	 * for the given value. Captions can contain some HTML markup which is
	 * admissible for wiki text, but no more. Result might have no entries
	 * but is always an array.
	 *
	 * @since 2.4
	 */
	public function createInfoLinks() {

		if ( $this->infoLinks !== [] ) {
			return $this->infoLinks;
		}

		if ( !$this->dataValue->isValid() ) {
			return [];
		}

		// Avoid any localization when generating the value
		$this->dataValue->setOutputFormat( '' );
		$dataItem = $this->dataValue->getDataItem();

		// For a subcategory we need the full prefixed form when
		// generating a browse link
		if ( $this->dataValue->getTypeID() === '__suc' ) {
			$this->dataValue->setOption( WikiPageValue::PREFIXED_FORM, true );
		}

		$value = $this->dataValue->getWikiValue();
		$property = $this->dataValue->getProperty();

		if ( $property !== null && in_array( $property->getKey(), $this->disabledLinksByKey ) ) {
			return [];
		}

		// InTextAnnotationParser will detect :: therefore avoid link
		// breakage by encoding the string
		if ( strpos( $value, '::' ) !== false && !InTextAnnotationParser::hasMarker( $value ) ) {
			$value = str_replace( ':', '-3A', $value );
		}

		if ( in_array( $this->dataValue->getTypeID(), $this->disabledBrowseLinksByType ) ) {
			$infoLink = Infolink::newPropertySearchLink( '+', $property->getLabel(), $value );
			$infoLink->setCompactLink( $this->compactLink );
		} elseif ( in_array( $dataItem->getDIType(), [ DataItem::TYPE_WIKIPAGE, DataItem::TYPE_CONTAINER ] ) ) {
			$infoLink = Infolink::newBrowsingLink( '+', $this->dataValue->getLongWikiText() );
			$infoLink->setCompactLink( $this->compactLink );
		} elseif ( $property !== null ) {
			$infoLink = Infolink::newPropertySearchLink( '+', $property->getLabel(), $value );
			$infoLink->setCompactLink( $this->compactLink );
		}

		$this->infoLinks[] = $infoLink;
		$this->hasSearchLink = $this->infoLinks !== [];

		 // add further service links
		if ( !$this->hasServiceLinks && $this->enabledServiceLinks ) {
			$this->addServiceLinks();
		}

		return $this->infoLinks;
	}

	/**
	 * Return text serialisation of info links. Ensures more uniform layout
	 * throughout wiki (Factbox, Property pages, ...).
	 *
	 * @param integer $outputformat Element of the SMW_OUTPUT_ enum
	 * @param Linker|null $linker
	 *
	 * @return string
	 */
	public function getInfolinkText( $outputformat, $linker = null ) {

		$result = '';
		$first = true;
		$extralinks = [];

		foreach ( $this->dataValue->getInfolinks() as $link ) {

			if ( $outputformat === SMW_OUTPUT_WIKI ) {
				$text = $link->getWikiText();
			} else {
				$text = $link->getHTML( $linker );
			}

			// the comment is needed to prevent MediaWiki from linking
			// URL-strings together with the nbsps!
			if ( $first ) {
				$result .= ( $outputformat === SMW_OUTPUT_WIKI ? '<!-- -->  ' : '&#160;&#160;' ) . $text;
				$first = false;
			} else {
				$extralinks[] = $text;
			}
		}

		if ( $extralinks !== [] ) {
			$result .= smwfEncodeMessages( $extralinks, 'service', '', false );
		}

		// #1453 SMW::on/off will break any potential link therefore just don't even try
		return !InTextAnnotationParser::hasMarker( $result ) ? $result : '';
	}

	/**
	 * Servicelinks are special kinds of infolinks that are created from
	 * current parameters and in-wiki specification of URL templates. This
	 * method adds the current property's servicelinks found in the
	 * messages. The number and content of the parameters is depending on
	 * the datatype, and the service link message is usually crafted with a
	 * particular datatype in mind.
	 */
	public function addServiceLinks() {

		if ( $this->hasServiceLinks ) {
			return;
		}

		$dataItem = null;

		if ( $this->dataValue->getProperty() !== null ) {
			$dataItem = $this->dataValue->getProperty()->getDiWikiPage();
		}

		// No property known, or not associated with a page!
		if ( $dataItem === null ) {
			return;
		}

		$args = $this->serviceLinkParameters;

		if ( $args === false ) {
			return; // no services supported
		}

		// add a 0 element as placeholder
		array_unshift( $args, '' );

		$servicelinks = $this->propertySpecificationLookup->getSpecification(
			$dataItem,
			new DIProperty( '_SERV' )
		);

		foreach ( $servicelinks as $servicelink ) {
			$this->makeLink( $servicelink, $args );
		}

		$this->hasServiceLinks = true;
	}

	private function makeLink( $dataItem, $args ) {

		if ( !( $dataItem instanceof DIBlob ) ) {
			return;
		}

		 // messages distinguish ' ' from '_'
		$args[0] = 'smw_service_' . str_replace( ' ', '_', $dataItem->getString() );
		$text = Message::get( $args, Message::TEXT, Message::CONTENT_LANGUAGE );
		$links = preg_split( "/[\n][\s]?/u", $text );

		foreach ( $links as $link ) {
			$linkdat = explode( '|', $link, 2 );

			if ( count( $linkdat ) == 2 ) {
				$this->addInfolink( Infolink::newExternalLink( $linkdat[0], trim( $linkdat[1] ) ) );
			}
		}
	}

}
