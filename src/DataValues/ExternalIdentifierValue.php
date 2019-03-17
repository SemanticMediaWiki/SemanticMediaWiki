<?php

namespace SMW\DataValues;

use SMW\DIProperty;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ExternalIdentifierValue extends StringValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '_eid';

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
		parent::parseUserValue( $value );
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

		$this->m_caption = str_replace( '\,', ',', $this->m_caption );

		if ( $linker === null ) {
			return $this->m_caption;
		}

		$uri = $this->makeUri(
			$this->m_dataitem->getString()
		);

		if ( !$this->isValid() ) {
			return '';
		}

		if ( $this->getOutputFormat() == 'nowiki' ) {
			$url = $this->makeNonlinkedWikiText( $uri );
		} else {
			$url = '['. $uri . ' '. $this->m_caption . ']';
		}

		return \Html::rawElement(
			'span',
			[
				'class' => 'plainlinks smw-eid'
			],
			$url
		);
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

		$this->m_caption = str_replace( '\,', ',', $this->m_caption );

		if ( $linker === null ) {
			return $this->m_caption;
		}

		$uri = $this->makeUri(
			$this->m_dataitem->getString()
		);

		if ( !$this->isValid() ) {
			return $this->m_caption;
		}

		return \Html::rawElement(
			'a',
			[
				'href'   => $uri,
				'target' => '_blank'
			],
			$this->m_caption
		);
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

		$dataValue = $this->dataValueServiceFactory->getDataValueFactory()->newDataValueByType(
			'_uri',
			$this->makeUri( $this->m_dataitem->getString() )
		);

		return $dataValue->getDataItem();
	}

	private function makeUri( $value ) {

		if ( $this->uri !== null ) {
			return $this->uri;
		}

		$dataItem = $this->dataValueServiceFactory->getPropertySpecificationLookup()->getExternalFormatterUri(
			$this->getProperty()
		);

		if ( $dataItem === null ) {
			$this->addErrorMsg( 'smw-datavalue-external-identifier-formatter-missing' );
			return;
		}

		$dataValue = $this->dataValueServiceFactory->getDataValueFactory()->newDataValueByItem(
			$dataItem,
			new DIProperty( '_PEFU' )
		);

		if ( $dataValue->getErrors() !== [] ) {
			$this->addError( $dataValue->getErrors() );
			return;
		}

		$parameters = [];

		// Requires multi substitution? Convention is: Foo(param1,param2,...)
		if ( $dataValue->hasMultiSubstitute() ) {
			$parameters = $this->filterParameters( $value );

			if ( $parameters === [] ) {
				$this->addErrorMsg(
					[
						'smw-datavalue-external-identifier-multi-substitute-parameters-missing',
						$this->getProperty()->getLabel(),
						$value
					]
				);
			}
		}

		return $this->uri = $dataValue->substituteAndFormatUri( $value, $parameters );
	}

	private function filterParameters( &$value ) {

		$parameters = [];
		$matches = [];

		if ( strpos( $value, '{' ) === false || strpos( $value, '}' ) === false ) {
			return $parameters;
		}

		// [[wp:article::Truid Aagesen{837787373}]]
		preg_match("/\{([^\]]*)\}/", $value, $matches );

		if ( $matches !== [] ) {
			// Remove extra parameters from caption
			$value = trim(
				str_replace( [ $matches[0] ], [ '' ], $value )
			);

			// Decode `\,` to distinguish it from the separator comma
			$parameters = explode( ',', str_replace( '\,', '%2C', $matches[1] ) );
		}

		$this->m_caption = $value;

		return $parameters;
	}

	private function makeNonlinkedWikiText( $url ) {
		return str_replace( ':', '&#58;', $url );
	}

}
