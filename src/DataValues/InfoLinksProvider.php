<?php

namespace SMW\DataValues;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\Message;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;
use SMWInfolink as Infolink;

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
	 * @var Infolink[]
	 */
	protected $infoLinks = array();

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
	 * @var boolean|array
	 */
	private $serviceLinkParameters = false;

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 */
	public function __construct( DataValue $dataValue ) {
		$this->dataValue = $dataValue;
	}

	/**
	 * @since 2.4
	 */
	public function init() {
		$this->infoLinks = array();
		$this->hasSearchLink = false;
		$this->hasServiceLinks = false;
		$this->enabledServiceLinks = true;
		$this->serviceLinkParameters = false;
	}

	/**
	 * @since 2.4
	 */
	public function disableServiceLinks() {
		$this->enabledServiceLinks = false;
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

		if ( $this->infoLinks !== array() ) {
			return $this->infoLinks;
		}

		if ( !$this->dataValue->isValid() || $this->dataValue->getProperty() === null ) {
			return array();
		}

		$value = $this->dataValue->getWikiValue();

		// InTextAnnotationParser will detect :: therefore avoid link
		// breakage by encoding the string
		if ( strpos( $value, '::' ) !== false && !$this->hasInternalAnnotationMarker( $value ) ) {
			$value = str_replace( ':', '-3A', $value );
		}

		$this->hasSearchLink = true;
		$this->infoLinks[] = Infolink::newPropertySearchLink(
			'+',
			$this->dataValue->getProperty()->getLabel(),
			$value
		);

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
		$extralinks = array();

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

		if ( $extralinks !== array() ) {
			$result .= smwfEncodeMessages( $extralinks, 'service', '', false );
		}

		// #1453 SMW::on/off will break any potential link therefore just don't even try
		return !$this->hasInternalAnnotationMarker( $result ) ? $result : '';
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

		if ( $this->dataValue->getProperty() !== null ) {
			$propertyDiWikiPage = $this->dataValue->getProperty()->getDiWikiPage();
		}

		if ( $propertyDiWikiPage === null ) {
			return; // no property known, or not associated with a page
		}

		$args = $this->serviceLinkParameters;

		if ( $args === false ) {
			return; // no services supported
		}

		array_unshift( $args, '' ); // add a 0 element as placeholder

		$servicelinks = ApplicationFactory::getInstance()->getCachedPropertyValuesPrefetcher()->getPropertyValues(
			$propertyDiWikiPage,
			new DIProperty( '_SERV' )
		);

		foreach ( $servicelinks as $dataItem ) {
			if ( !( $dataItem instanceof DIBlob ) ) {
				continue;
			}

			$args[0] = 'smw_service_' . str_replace( ' ', '_', $dataItem->getString() ); // messages distinguish ' ' from '_'
			$text = Message::get( $args, Message::TEXT, Message::CONTENT_LANGUAGE );
			$links = preg_split( "/[\n][\s]?/u", $text );

			foreach ( $links as $link ) {
				$linkdat = explode( '|', $link, 2 );

				if ( count( $linkdat ) == 2 ) {
					$this->addInfolink( Infolink::newExternalLink( $linkdat[0], trim( $linkdat[1] ) ) );
				}
			}
		}

		$this->hasServiceLinks = true;
	}

	private function hasInternalAnnotationMarker( $value ) {
		return strpos( $value, 'SMW::off' ) !== false || strpos( $value, 'SMW::on' ) !== false;
	}

}
