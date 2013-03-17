<?php

namespace SMW;

use Title;
use WikiPage;
use ParserOutput;
use MWException;

use SMWDIWikiPage;
use SMWPropertyValue;
use SMWSemanticData;
use SMWDataValueFactory;
use SMWDIProperty;
use SMWDIBlob;
use SMWDIBoolean;
use SMWDITime;

/**
 * Interface handling semantic data storage to a ParserOutput instance
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
	 * Clears all data for the given instance
	 *
	 * @since 1.9
	 */
	public function clearData();

	/**
	 * Stores semantic data to the database
	 *
	 * @since 1.9
	 */
	public function storeData( $runAsJob );

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
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ParserData implements IParserData {

	/**
	 * Represents Title object
	 * @var $title
	 */
	protected $title;

	/**
	 * Represents ParserOutput object
	 * @var $parserOutput
	 */
	protected $parserOutput;

	/**
	 * Represents SMWSemanticData object
	 * @var $semanticData
	 */
	protected $semanticData;

	/**
	 * Represents collected errors
	 * @var $errors
	 */
	protected $errors = array();

	/**
	 * Represents invoked GLOBALS
	 * @var $options
	 */
	protected $options;

	/**
	 * Allows explicitly to switch storage method, MW 1.21 comes with a new
	 * method setExtensionData/getExtensionData in how the ParserOutput
	 * stores arbitrary data
	 *
	 * MW 1.21 unit tests are passed but real page content did vanished
	 * therefore for now disable this feature for MW 1.21 as well
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
	 * @param \ParserOutput $parserOutput
	 * @param array $options
	 */
	public function __construct( Title $title, ParserOutput $parserOutput, array $options = array() ) {
		$this->title = $title;
		$this->parserOutput = $parserOutput;
		$this->options = $options;
		$this->setData();
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
	 * @return \SMWDIWikiPage
	 */
	public function getSubject() {
		return SMWDIWikiPage::newFromTitle( $this->title );
	}

	/**
	 * Returns collected errors occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return array_unique( $this->errors );
	}

	/**
	 * Returns boolean to indicate if an error appeared during processing
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
	 * @return \SMWSemanticData
	 */
	public function getData() {
		return $this->semanticData;
	}

	/**
	 * FIXME use getData() instead
	 * AskParserFunctionTest
	 *
	 * @since 1.9
	 *
	 * @return \SMWSemanticData
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	/**
	 * Clears all data for the given instance
	 *
	 * @since 1.9
	 */
	public function clearData() {
		$this->semanticData = new SMWSemanticData( $this->getSubject() );
	}

	/**
	 * Stores semantic data to the database
	 *
	 * @since 1.9
	 *
	 * @param boolean $runAsJob
	 */
	public function storeData( $runAsJob = true ) {
		// FIXME get rid of the static method
		\SMWParseData::storeData( $this->getOutput(), $this->getTitle(), $runAsJob );
	}

	/**
	 * Init semanticData container either from the ParserOutput object
	 * or if not available use the subject
	 *
	 * @since 1.9
	 */
	protected function setData() {
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
	 * Update ParserOutput with processed semantic data
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

		wfProfileIn(  __METHOD__ );

		$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDi = $propertyDv->getDataItem();

		if ( !$propertyDi->isInverse() ) {
			$valueDv = SMWDataValueFactory::newPropertyObjectValue( $propertyDi, $valueString,
				false, $this->semanticData->getSubject() );
			$this->semanticData->addPropertyObjectValue( $propertyDi, $valueDv->getDataItem() );

			// Take note of the error for storage (do this here and not in storage, thus avoiding duplicates).
			if ( !$valueDv->isValid() ) {
				$this->semanticData->addPropertyObjectValue(
					new SMWDIProperty( SMWDIProperty::TYPE_ERROR ),
					$propertyDi->getDiWikiPage()
				);
				$this->setError( $valueDv->getErrors() );
			}
		} else {
			// FIXME Message object from context
			$this->setError( array( wfMessage( 'smw_noinvannot' )->inContentLanguage()->text() ) );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Add category information
	 *
	 * Part of this code was entangled in SMWParseData::onParserAfterTidy
	 * which has now been separated and is called from
	 * SMWHooks::onParserAfterTidy
	 *
	 * @note Fetches category information and other final settings
	 * from parser output, so that they are also replicated in SMW for more
	 * efficient querying.
	 *
	 * @since 1.9
	 *
	 * @param array $categoryLinks
	 *
	 * @return boolean|null
	 */
	public function addCategories( array $categoryLinks ) {
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			return true;
		}

		// Iterate over available categories
		foreach ( $categoryLinks as $catname ) {
			if ( $this->options['smwgCategoriesAsInstances'] && ( $this->getTitle()->getNamespace() !== NS_CATEGORY ) ) {
				$this->semanticData->addPropertyObjectValue(
					new SMWDIProperty( SMWDIProperty::TYPE_CATEGORY_INSTANCE ),
					new SMWDIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}

			if ( $this->options['smwgUseCategoryHierarchy'] && ( $this->getTitle()->getNamespace() === NS_CATEGORY ) ) {
				$this->semanticData->addPropertyObjectValue(
					new SMWDIProperty( SMWDIProperty::TYPE_SUBCATEGORY ),
					new SMWDIWikiPage( $catname, NS_CATEGORY, '' )
				);
			}
		}
	}

	/**
	 * Add default sort
	 *
	 * @since 1.9
	 *
	 * @param string $defaultSort
	 *
	 * @return boolean|null
	 */
	public function addDefaultSort( $defaultSort ) {
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			return true;
		}

		$sortkey = $defaultSort ? $defaultSort : str_replace( '_', ' ', $this->title->getDBkey() );
		$this->semanticData->addPropertyObjectValue(
			new SMWDIProperty( SMWDIProperty::TYPE_SORTKEY ),
			new SMWDIBlob( $sortkey )
		);
	}

	/**
	 * Add additional information that is related to special properties
	 * e.g. modification date, the last edit date etc.
	 *
	 * @since 1.9
	 *
	 * @param \WikiPage $wikiPage
	 * @param \Revision $revision
	 * @param \User $user
	 *
	 * @return boolean|null
	 */
	public function addSpecialProperties( \WikiPage $wikiPage, \Revision $revision, \User $user ) {
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			return true;
		}

		// Keeps temporary account on processed properties
		$processedProperty = array();

		foreach ( $this->options['smwgPageSpecialProperties'] as $propertyId ) {

			// Ensure that only special properties are added that are registered
			// and only added once
			if ( SMWDIProperty::getPredefinedPropertyTypeId( $propertyId ) === '' &&
				array_key_exists( $propertyId, $processedProperty ) ) {
				continue;
			}

			$propertyDI = new SMWDIProperty( $propertyId );

			// Don't do a double round
			if ( $this->semanticData->getPropertyValues( $propertyDI ) !== array() ) {
				$processedProperty[ $propertyId ] = true;
				//var_dump( __METHOD__, 'check double', $propertyDI->getLabel() );
				continue;
			}

			switch ( $propertyId ) {
				case SMWDIProperty::TYPE_MODIFICATION_DATE :
					$dataValue = SMWDITime::newFromTimestamp( $wikiPage->getTimestamp() );
					break;
				case SMWDIProperty::TYPE_CREATION_DATE :
					// Expensive getFirstRevision() initiates a revision table
					// read and is not cached
					$dataValue = SMWDITime::newFromTimestamp( $this->title->getFirstRevision()->getTimestamp() );
					break;
				case SMWDIProperty::TYPE_NEW_PAGE :
					// Expensive isNewPage() does a database read
					// $dataValue = new SMWDIBoolean( $this->title->isNewPage() );
					$dataValue = new SMWDIBoolean( $revision->getParentId() !== '' );
					break;
				case SMWDIProperty::TYPE_LAST_EDITOR :
					//$revision = Revision::newFromTitle( $title );
					//$user = User::newFromId( $revision->getUser() );
					$dataValue = SMWDIWikiPage::newFromTitle( $user->getUserPage() );
					break;
			}

			if ( $dataValue instanceof SMWDataItem ) {
				$processedProperty[ $propertyId ] = true;
				$this->semanticData->addPropertyObjectValue( $propertyDI, $dataValue );
			}
		}
	}
}
