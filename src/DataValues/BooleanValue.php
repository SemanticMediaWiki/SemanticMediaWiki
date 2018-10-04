<?php

namespace SMW\DataValues;

use SMW\Localizer;
use SMW\Message;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIBoolean as DIBoolean;

/**
 * This datavalue implements the handling of Boolean datavalues.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class BooleanValue extends DataValue {

	/**
	 * The text to write for "true" if a custom output format was set.
	 * @var string
	 */
	protected $trueCaption;

	/**
	 * The text to write for "false" if a custom output format was set.
	 * @var string
	 */
	protected $falseCaption;

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( $typeid );
	}

	/**
	 * @see DataValue::parseUserValue
	 */
	protected function parseUserValue( $value ) {

		$value = trim( $value );

		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		$this->m_dataitem = new DIBoolean(
			 $this->doParseBoolValue( $value )
		);
	}

	/**
	 * @see DataValue::loadDataItem
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( $dataItem->getDIType() !== DataItem::TYPE_BOOLEAN ) {
			return false;
		}

		$this->m_dataitem = $dataItem;
		$this->m_caption = $this->getStandardCaption( true );

		return true;
	}

	/**
	 * @see DataValue::setOutputFormat
	 */
	public function setOutputFormat( $formatstring ) {

		if ( $formatstring == $this->m_outformat ) {
			return;
		}

		unset( $this->trueCaption );
		unset( $this->falseCaption );

		if ( $formatstring === '' ) { // no format
			// (unsetting the captions is exactly the right thing here)
		} elseif ( strtolower( $formatstring ) == '-' ) { // "plain" format
			$this->trueCaption = 'true';
			$this->falseCaption = 'false';
		} elseif ( strtolower( $formatstring ) == 'num' ) { // "numeric" format
			$this->trueCaption = 1;
			$this->falseCaption = 0;
		} elseif ( strtolower( $formatstring ) == 'tick' ) { // "tick" format
			$this->trueCaption = '✓';
			$this->falseCaption = '✕';
		} elseif ( strtolower( $formatstring ) == 'x' ) { // X format
			$this->trueCaption = '<span style="font-family: sans-serif; ">X</span>';
			$this->falseCaption = '&nbsp;';
		} else { // format "truelabel, falselabel" (hopefully)
			$captions = explode( ',', $formatstring, 2 );
			if ( count( $captions ) == 2 ) { // note: escaping needed to be safe; MW-sanitising would be an alternative
				$this->trueCaption = \Sanitizer::removeHTMLtags( trim( $captions[0] ) );
				$this->falseCaption = \Sanitizer::removeHTMLtags( trim( $captions[1] ) );
			} // else: no format that is recognised, ignore
		}

		// Localized version
		if ( strpos( $formatstring, 'LOCL' ) !== false ) {
			$this->setLocalizedCaptions( $formatstring );
		}

		$this->m_caption = $this->getStandardCaption( true );
		$this->m_outformat = $formatstring;
	}

	/**
	 * @see DataValue::getShortWikiText
	 */
	public function getShortWikiText( $linker = null ) {
		return $this->m_caption;
	}

	/**
	 * @see DataValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->m_caption;
	}

	/**
	 * @see DataValue::getLongWikiText
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->isValid() ? $this->getStandardCaption( true ) : $this->getErrorText();
	}

	/**
	 * @see DataValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->isValid() ? $this->getStandardCaption( true ) : $this->getErrorText();
	}

	/**
	 * @see DataValue::getWikiValue
	 */
	public function getWikiValue() {
		return $this->getFirstBooleanCaptionFrom(
			$this->isValid() && $this->m_dataitem->getBoolean() ? 'smw_true_words' : 'smw_false_words',
			Message::CONTENT_LANGUAGE
		);
	}

	/**
	 * @since 1.6
	 *
	 * @return boolean
	 */
	public function getBoolean() {
		return !$this->isValid() ? false : $this->m_dataitem->getBoolean();
	}

	/**
	 * Get text for displaying the value of this property, or false if not
	 * valid.
	 * @param $useformat bool, true if the output format should be used, false if the returned text should be parsable
	 * @return string
	 */
	protected function getStandardCaption( $useformat ) {

		if ( !$this->isValid() ) {
			return false;
		}

		if ( $useformat && ( isset( $this->trueCaption ) ) ) {
			return $this->m_dataitem->getBoolean() ? $this->trueCaption : $this->falseCaption;
		}

		return $this->getFirstBooleanCaptionFrom(
			$this->m_dataitem->getBoolean() ? 'smw_true_words' : 'smw_false_words',
			$this->getOption( 'content.language' )
		);
	}

	private function doParseBoolValue( $value ) {

		// Use either the global or page related content language
		$contentLanguage = $this->getOption( 'content.language' );

		$lcv = mb_strtolower( $value );
		$boolvalue = false;

		if ( $lcv === '1' ) {
			$boolvalue = true;
		} elseif ( $lcv === '0' ) {
			$boolvalue = false;
		} elseif ( in_array( $lcv, $this->getBooleanWordsFrom( 'smw_true_words', $contentLanguage, 'true' ), true ) ) {
			$boolvalue = true;
		} elseif ( in_array( $lcv, $this->getBooleanWordsFrom( 'smw_false_words', $contentLanguage, 'false' ), true ) ) {
			$boolvalue = false;
		} else {
			$this->addErrorMsg(
				[ 'smw_noboolean', $value ],
				Message::TEXT,
				Message::USER_LANGUAGE
			);
		}

		return $boolvalue;
	}

	private function setLocalizedCaptions( &$formatstring ) {

		if ( !( $languageCode = Localizer::getLanguageCodeFrom( $formatstring ) ) ) {
			$languageCode = $this->getOption( 'user.language' );
		}

		$this->trueCaption = $this->getFirstBooleanCaptionFrom(
			'smw_true_words',
			$languageCode
		);

		$this->falseCaption = $this->getFirstBooleanCaptionFrom(
			'smw_false_words',
			$languageCode
		);
	}

	private function getFirstBooleanCaptionFrom( $msgKey, $languageCode = null ) {

		$vals = $this->getBooleanWordsFrom(
			$msgKey,
			$languageCode
		);

		return reset( $vals );
	}

	private function getBooleanWordsFrom( $msgKey, $languageCode = null, $canonicalForm = null ) {

		$vals = explode(
			',',
			Message::get( $msgKey, Message::TEXT, $languageCode )
		);

		if ( $canonicalForm !== null ) {
			$vals[] = $canonicalForm;
		}

		return $vals;
	}

}
