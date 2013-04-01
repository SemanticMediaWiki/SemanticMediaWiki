<?php

namespace SMW;

use Title;
use ParserOutput;
use MWException;

use SMWDIWikiPage;
use SMWPropertyValue;
use SMWSemanticData;
use SMWDataValueFactory;
use SMWDIProperty;

/**
 * Interface handling semantic data from a ParserOuput object
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author mwjames
 */
interface IParserData {

	/**
	 * The constructor requires a Title and ParserOutput object
	 */

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle();

	/**
	 * Returns ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return ParserOutput
	 */
	public function getOutput();

	/**
	 * Update ParserOoutput with processed semantic data
	 *
	 * @since 1.9
	 */
	public function updateOutput();

	/**
	 * Get semantic data
	 *
	 * @since 1.9
	 *
	 * @return SMWSemanticData
	 */
	public function getData();

	/**
	 * Stores semantic data to the database
	 *
	 * @since 1.9
	 */
	public function storeData();

	/**
	 * Returns an report about activities that occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getReport();

}

/**
 * Class that provides access to the semantic data object generated from either
 * the ParserOuput or subject provided (no static binding as in SMWParseData)
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 */
class ParserData implements IParserData {

	/**
	 * Defines SMWSemanticData object
	 * @var $semanticData
	 */
	protected $semanticData;

	/**
	 * Holds collected errors
	 * @var $errors
	 */
	protected $errors = array();

	/**
	 * Defines SMWDIWikiPage object
	 * @var $subject
	 */
	protected $title;

	/**
	 * Allow explicitly to switch storage method
	 * MW 1.21 comes with a new method setExtensionData/getExtensionData
	 * in how the ParserOutput stores arbitrary data
	 *
	 * If turned true, MW 1.21 unit tests are passed but real page content
	 * vanishes therefore for now disable this feature for MW 1.21 as well
	 *
	 * The unit test will use either setting to test the storage method
	 *
	 * @var $extensionData
	 */
	protected $useExtensionData = false;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param \Title $title
	 */
	public function __construct( Title $title, ParserOutput $parserOutput  ) {
		$this->title = $title;
		$this->parserOutput = $parserOutput;
		$this->initSemanticData();
	}

	/**
	 * Returns Title object
	 *
	 * @since 1.9
	 *
	 * @return \Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Returns ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @return \ParserOutput
	 */
	public function getOutput() {
		return $this->parserOutput;
	}

	/**
	 * Returns SMWDIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return SMWDIWikiPage
	 */
	public function getSubject() {
		return new SMWDIWikiPage(
			$this->title->getDBkey(),
			$this->title->getNamespace(),
			$this->title->getInterwiki()
		);
	}

	/**
	 * Returns errors collected during processing the semanticData container
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return array_unique( $this->errors );
	}

	/**
	 * Returns boolean to indicate if errors appeared during processing
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function hasError() {
		return $this->errors !== array();
	}

	/**
	 * Collect and set error array
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function setError( array $errors ) {
		return $this->errors = array_merge ( $errors, $this->errors );
	}

	/**
	 * Encode and report errors that appeared during processing
	 *
	 * @since  1.9
	 *
	 * @return string
	 */
	public function getReport() {
		return smwfEncodeMessages( $this->getErrors() );
	}

	/**
	 * Returns instantiated semanticData container
	 *
	 * @since 1.9
	 *
	 * @return SMWSemanticData
	 */
	public function getData() {
		return $this->getSemanticData();
	}

	/**
	 * Stores semantic data
	 *
	 * @since 1.9
	 */
	public function storeData() {
		return $this->updateParserOutput();
	}

	/**
	 * Returns instantiated semanticData container
	 *
	 * @since 1.9
	 *
	 * @return SMWSemanticData
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	/**
	 * Init semanticData container either from the ParserOutput object
	 * or if not available use the subject
	 *
	 * @since 1.9
	 */
	protected function initSemanticData() {
		if ( method_exists( $this->parserOutput, 'getExtensionData' ) && $this->useExtensionData ) {
			$this->semanticData = $this->parserOutput->getExtensionData( 'smwdata' );
		} elseif ( isset( $this->parserOutput->mSMWData ) ) {
			$this->semanticData = $this->parserOutput->mSMWData;
		}

		// Setup data container
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			$this->semanticData = new SMWSemanticData( $this->getSubject() );
		}
	}

	/**
	 * Add value string to the instantiated semanticData container
	 *
	 * @since 1.9
	 *
	 * @param string $propertyName
	 * @param string $valueString
	 *
	 * @throws MWException
	 */
	public function addPropertyValueString( $propertyName, $valueString ) {
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			throw new MWException( 'The subject container is not initialized' );
		}

		$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDi = $propertyDv->getDataItem();

		if ( !$propertyDi->isInverse() ) {
			$valueDv = SMWDataValueFactory::newPropertyObjectValue( $propertyDi, $valueString,
				false, $this->semanticData->getSubject() );
			$this->semanticData->addPropertyObjectValue( $propertyDi, $valueDv->getDataItem() );

			// Take note of the error for storage (do this here and not in storage, thus avoiding duplicates).
			if ( !$valueDv->isValid() ) {
				$this->semanticData->addPropertyObjectValue( new SMWDIProperty( SMWDIProperty::TYPE_ERROR ),
					$propertyDi->getDiWikiPage() );
				$this->setError( $valueDv->getErrors() );
			}
		} else {
			// FIXME Message object from context
			 $this->setError( array( wfMessage( 'smw_noinvannot' )->inContentLanguage()->text() ) );
		}
	}

	/**
	 * Update ParserOoutput with processed semantic data
	 *
	 * @since 1.9
	 *
	 * @throws MWException
	 */
	public function updateOutput(){
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			throw new MWException( 'The semantic data container is not available' );
		}

		if ( method_exists( $this->parserOutput, 'setExtensionData' ) && $this->useExtensionData ) {
			$this->parserOutput->setExtensionData( 'smwdata', $this->semanticData );
		} else {
			$this->parserOutput->mSMWData = $this->semanticData;
		}
	}
}
