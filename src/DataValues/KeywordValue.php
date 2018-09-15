<?php

namespace SMW\DataValues;

use SMW\DIProperty;
use SMW\Localizer;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWInfolink as Infolink;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class KeywordValue extends StringValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '_keyw';

	/**
	 * @var string|null
	 */
	private $uri = null;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $value ) {

		// For the normal blob field setup multi-byte requires more space and
		// since we use the o_hash field to store the normalized content and
		// as match field, ensure to have enough space to actually store
		// a mb keyword
		$maxLength = mb_detect_encoding( $value, 'ASCII', true ) ? 150 : 85;

		if ( mb_strlen( $value, 'utf8' ) > $maxLength ) {
			$this->addErrorMsg( [ 'smw-datavalue-keyword-maximum-length', $maxLength ] );
			return;
		}

		if ( $this->getOption( self::OPT_QUERY_COMP_CONTEXT ) || $this->getOption( self::OPT_QUERY_CONTEXT ) ) {
			$value = DIBlob::normalize( $value );
		}

		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		parent::parseUserValue( $value );

		$this->m_dataitem->setOption( 'is.keyword', true );
	}

	/**
	 * @see DataValue::getDataItem
	 */
	public function getDataItem() {

		if ( $this->isValid() && $this->getOption( 'is.search' ) ) {
			return new DIBlob( DIBlob::normalize( $this->m_dataitem->getString() ) );
		}

		return parent::getDataItem();
	}

	/**
	 * @see DataValue::getShortWikiText
	 *
	 * @param string $value
	 */
	public function getShortWikiText( $linker = null ) {

		if ( !$this->isValid() ) {
			return '';
		}

		if ( !$this->m_caption ) {
			$this->m_caption = $this->m_dataitem->getString();
		}

		if ( $linker === null ) {
			return $this->m_caption;
		}

		$uri = $this->makeUri(
			$this->m_dataitem->getString(),
			SMW_OUTPUT_WIKI,
			$linker
		);

		if ( $uri === '' ) {
			return $this->m_caption;
		}

		return $uri;
	}

	/**
	 * @see StringValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {

		if ( !$this->isValid() ) {
			return '';
		}

		if ( !$this->m_caption ) {
			$this->m_caption = $this->m_dataitem->getString();
		}

		if ( $linker === null ) {
			return $this->m_caption;
		}

		$uri = $this->makeUri(
			$this->m_dataitem->getString(),
			SMW_OUTPUT_HTML,
			$linker
		);

		if ( $uri === '' ) {
			return $this->m_caption;
		}

		return $uri;
	}

	/**
	 * @see StringValue::getLongWikiText
	 */
	public function getLongWikiText( $linked = null ) {
		return $this->getShortWikiText( $linked );
	}

	/**
	 * @see StringValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->getShortHTMLText( $linker );
	}

	/**
	 * @since 2.5
	 *
	 * @return DataItem
	 */
	public function getUri() {

		if ( !$this->isValid() ) {
			return '';
		}

		$uri = $this->makeUri(
			$this->m_dataitem->getString(),
			SMW_OUTPUT_RAW
		);

		if ( $uri === '' ) {
			return '';
		}

		$dataValue = $this->dataValueServiceFactory->getDataValueFactory()->newDataValueByType(
			'_uri',
			$uri
		);

		return $dataValue->getDataItem();
	}

	private function makeUri( $value, $outputformat, $linker = null ) {

		if ( $this->uri !== null ) {
			return $this->uri;
		}

		$propertySpecificationLookup = $this->dataValueServiceFactory->getPropertySpecificationLookup();

		// Formatter schema?
		$dataItems = $propertySpecificationLookup->getSpecification(
			$this->getProperty(),
			new DIProperty( '_FORMAT_SCHEMA' )
		);

		if ( $dataItems === [] ) {
			return '';
		}

		$dataItem = end( $dataItems );

		$dataItems = $propertySpecificationLookup->getSpecification(
			$dataItem,
			new DIProperty( '_SCHEMA_DEF' )
		);

		if ( $dataItems === [] ) {
			return '';
		}

		$dataItem = end( $dataItems );

		$link = $this->getFormatLink( $dataItem, $value );

		if ( $link === '' ) {
			return '';
		}

		$this->uri = $link->getText( $outputformat );

		$this->uri = Localizer::getInstance()->getCanonicalizedUrlByNamespace(
			NS_SPECIAL,
			$this->uri
		);

		return $this->uri;
	}

	private function getFormatLink( $dataItem, $value ) {

		$infolink = '';

		$data = json_decode(
			$dataItem->getString(),
			true
		);

		// Schema enforced
		if ( $data['type'] !== 'LINK_FORMAT_SCHEMA' ) {
			return '';
		}

		if ( !isset( $data['rule']['link_to'] ) ) {
			return '';
		}

		$link_to = $data['rule']['link_to'];
		$label = $this->getProperty()->getLabel();

		if ( $link_to === 'SPECIAL_ASK' ) {
			$infolink = Infolink::newInternalLink( $this->m_caption, ':Special:Ask', false, [] );
			$infolink->setParameter( "[[$label::$value]]", false );
			$infolink->setCompactLink( $this->getOption( KeywordValue::OPT_COMPACT_INFOLINKS, false ) );

			foreach ( $data['rule']['parameters'] as $key => $value ) {

				if ( $key === 'title' || $key === 'msg' ) {
					$key = "b$key";
				}

				if ( $key === 'printouts' ) {
					foreach ( $value as $v ) {
						$infolink->setParameter( "?$v" );
					}
				} else {
					$infolink->setParameter( $value, $key );
				}
			}

		} elseif ( $link_to === 'SPECIAL_SEARCH_BY_PROPERTY' ) {
			$infolink = Infolink::newInternalLink( $this->m_caption, ':Special:SearchByProperty', false, [] );
			$infolink->setCompactLink( $this->getOption( KeywordValue::OPT_COMPACT_INFOLINKS, false ) );
			$infolink->setParameter( ":$label" );
			$infolink->setParameter( $value );
		}

		return $infolink;
	}

	private function makeNonlinkedWikiText( $url ) {
		return str_replace( ':', '&#58;', $url );
	}

}
