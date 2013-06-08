<?php

namespace SMW;

use Title;
use WikiPage;
use ParserOutput;
use MWException;
use Job;

use SMWStore;
use SMWDataValue;
use SMWDIWikiPage;
use SMWSemanticData;
use SMWDIProperty;
use SMWDIBlob;
use SMWDIBoolean;
use SMWDITime;
use SMWUpdateJob;

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
	 * Updates the store with semantic data fetched from a ParserOutput object
	 *
	 * @since 1.9
	 */
	public function updateStore();

	/**
	 * Returns errors that occurred during processing
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getErrors();

}

/**
 * Class that provides access to the semantic data object generated from either
 * the ParserOuput or subject provided (no static binding as in SMWParseData)
 *
 * The responsibility of this class is to handle mainly the parserOutput object,
 * and one could argue that addPropertyValueString() has to be removed, while
 * addCategories(), addDefaultSort(), addSpecialProperties() are manipulating
 * the semantic data container invoked from the parserOutput object.
 *
 * UpdateStore(), getDiffPropertyTypes(), getDiffConversionFactors() are
 * responsible to update the store with the processed semantic data container.
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
	 * @var Title
	 */
	protected $title;

	/**
	 * Represents ParserOutput object
	 * @var ParserOutput
	 */
	protected $parserOutput;

	/**
	 * Represents SMWSemanticData object
	 * @var SMWSemanticData
	 */
	protected $semanticData;

	/**
	 * Represents collected errors
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Represents invoked GLOBALS
	 * @var array
	 */
	protected $options;

	/**
	 * Represents invoked $smwgEnableUpdateJobs
	 * @var $updateJobs
	 */
	protected $updateJobs = true;

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
		$this->updateJobs = $GLOBALS['smwgEnableUpdateJobs'];
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
		return $this->errors;
	}

	/**
	 * Collect and set error array
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function addError( array $errors ) {
		return $this->errors = array_merge ( $errors, $this->errors );
	}

	/**
	 * Explicitly disable update jobs (e.g when running store update
	 * in the job queue)
	 *
	 * @since 1.9
	 */
	public function disableUpdateJobs() {
		$this->updateJobs = false;
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
	 * Clears all data for the given instance
	 *
	 * @since 1.9
	 */
	public function clearData() {
		$this->semanticData = new SMWSemanticData( $this->getSubject() );
	}

	/**
	 * Initializes the semantic data container either from the ParserOutput or
	 * if not available a new container is being created
	 *
	 * @note MW 1.21+ use getExtensionData()
	 *
	 * @since 1.9
	 */
	protected function setData() {
		if ( method_exists( $this->parserOutput, 'getExtensionData' ) ) {
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
	 * @note MW 1.21+ use setExtensionData()
	 *
	 * @since 1.9
	 *
	 * @throws MWException
	 */
	public function updateOutput(){
		if ( !( $this->semanticData instanceof SMWSemanticData ) ) {
			throw new MWException( 'The semantic data container is not available' );
		}

		if ( method_exists( $this->parserOutput, 'setExtensionData' ) ) {
			$this->parserOutput->setExtensionData( 'smwdata', $this->semanticData );
		} else {
			$this->parserOutput->mSMWData = $this->semanticData;
		}
	}

	/**
	 * This method adds a data value to the semantic data container
	 *
	 * @par Example:
	 * @code
	 * $parserData = new SMW\ParserData(
	 *  $parser->getTitle(),
	 *  $parser->getOutput(),
	 *  $settings;
	 * )
	 *
	 * $dataValue = SMWDataValueFactory::newPropertyValue( $userProperty, $userValue )
	 * $parserData->addPropertyValue( $dataValue )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param SMWDataValue $dataValue
	 */
	public function addPropertyValue( SMWDataValue $dataValue ) {
		wfProfileIn(  __METHOD__ );

		if ( $dataValue->getProperty() instanceof SMWDIProperty ) {
			if ( !$dataValue->isValid() ) {
				$this->semanticData->addPropertyObjectValue(
					new SMWDIProperty( SMWDIProperty::TYPE_ERROR ),
					$dataValue->getProperty()->getDiWikiPage()
				);
				$this->addError( $dataValue->getErrors() );
			} else {
				$this->semanticData->addPropertyObjectValue(
					$dataValue->getProperty(),
					$dataValue->getDataItem()
				);
			}
		} else {
			$this->addError( $dataValue->getErrors() );
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
	 * @see SMWHooks::onParserAfterTidy
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
					new SMWDIProperty( SMWDIProperty::TYPE_CATEGORY ),
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
	 * @see SMWHooks::onParserAfterTidy
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

		// Keeps temporary account over processed properties
		$processedProperty = array();

		foreach ( $this->options['smwgPageSpecialProperties'] as $propertyId ) {

			// Ensure that only special properties are added that are registered
			// and only added once
			if ( ( SMWDIProperty::getPredefinedPropertyTypeId( $propertyId ) === '' ) ||
				( array_key_exists( $propertyId, $processedProperty ) ) ) {
				continue;
			}

			$propertyDI = new SMWDIProperty( $propertyId );

			// Don't do a double round
			if ( $this->semanticData->getPropertyValues( $propertyDI ) !== array() ) {
				$processedProperty[ $propertyId ] = true;
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
					$dataValue = SMWDIWikiPage::newFromTitle( $user->getUserPage() );
					break;
			}

			if ( is_a( $dataValue, 'SMWDataItem' ) ) {
				$processedProperty[ $propertyId ] = true;
				$this->semanticData->addPropertyObjectValue( $propertyDI, $dataValue );
			}
		}
	}

	/**
	 * Updates the store with semantic data attached to a ParserOutput object
	 *
	 * This function takes care of storing the collected semantic data and takes
	 * care of clearing out any outdated entries for the processed page. It assume that
	 * parsing has happened and that all relevant data is contained in the provided parser
	 * output.
	 *
	 * Optionally, this function also takes care of triggering indirect updates that might be
	 * needed for overall database consistency. If the saved page describes a property or data type,
	 * the method checks whether the property type, the data type, the allowed values, or the
	 * conversion factors have changed. If so, it triggers SMWUpdateJobs for the relevant articles,
	 * which then asynchronously update the semantic data in the database.
	 *
	 * @todo FIXME: Some job generations here might create too many jobs at once
	 * on a large wiki. Use incremental jobs instead.
	 *
	 * To disable jobs either set $smwgEnableUpdateJobs = false or invoke
	 * SMW\ParserData::disableUpdateJobs()
	 *
	 * Called from SMWUpdateJob::run, SMWHooks::onLinksUpdateConstructed,
	 * SMWHooks::onParserAfterTidy
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function updateStore() {
		wfProfileIn( __METHOD__ );

		// FIXME get rid of globals and use options array instead while
		// invoking the constructor
		$this->options = array(
			'smwgDeclarationProperties' => $GLOBALS['smwgDeclarationProperties'],
			'smwgPageSpecialProperties' => $GLOBALS['smwgPageSpecialProperties']
		);

		$namespace = $this->title->getNamespace();
		$wikiPage = WikiPage::factory( $this->title );
		$revision = $wikiPage->getRevision();
		$store    = smwfGetStore();
		$jobs     = array();

		// Make sure to have a valid revision (null means delete etc.)
		// Check if semantic data should be processed and displayed for a page in
		// the given namespace
		$processSemantics = $revision !== null ? smwfIsSemanticsProcessed( $namespace ) : false;

		if ( $processSemantics ) {
			$user = \User::newFromId( $revision->getUser() );
			$this->addSpecialProperties( $wikiPage, $revision, $user );
		} else {
			// data found, but do all operations as if it was empty
			$this->semanticData = new SMWSemanticData( $this->getSubject() );
		}

		// Careful: storage access must happen *before* the storage update;
		// even finding uses of a property fails after its type was changed.
		if ( $this->updateJobs && ( $namespace === SMW_NS_PROPERTY ) ) {
			$this->getDiffPropertyTypes( $store, $jobs );
		} else if ( $this->updateJobs && ( $namespace === SMW_NS_TYPE ) ) {
			$this->getDiffConversionFactors( $store, $jobs );
		}

		// Actually store semantic data, or at least clear it if needed
		if ( $processSemantics ) {
			$store->updateData( $this->semanticData );
		} else {
			$store->clearData( $this->semanticData->getSubject() );
		}

		// Job::batchInsert was deprecated in MW 1.21
		// @see JobQueueGroup::singleton()->push( $job );
		if ( $jobs !== array() ) {
			Job::batchInsert( $jobs );
		}

		wfProfileOut( __METHOD__ );

		return true;
	}

	/**
	 * Helper method to handle diff/change in type property pages
	 *
	 * @note If it is a property, then we need to check if the type or the
	 * allowed values have been changed.
	 *
	 * @since 1.9
	 *
	 * @param SMWStore $store
	 * @param array &$jobs
	 */
	protected function getDiffPropertyTypes( SMWStore $store, array &$jobs ) {
		wfProfileIn( __METHOD__ );

		$updatejobflag = false;
		$ptype = new SMWDIProperty( SMWDIProperty::TYPE_HAS_TYPE );

		// Get values from the store
		$oldtype = $store->getPropertyValues(
			$this->semanticData->getSubject(),
			$ptype
		);

		// Get values currently hold by the semantic container
		$newtype = $this->semanticData->getPropertyValues( $ptype );

		// Compare old and new type
		if ( !$this->equalDatavalues( $oldtype, $newtype ) ) {
			$updatejobflag = true;
		} else {
			// Compare values (in case of _PVAL (allowed values) for a
			// property change must be processed again)
			foreach ( $this->options['smwgDeclarationProperties'] as $prop ) {

				$dataItem = new SMWDIProperty( $prop );
				$oldValues = $store->getPropertyValues(
					$this->semanticData->getSubject(),
					$dataItem
				);
				$newValues = $this->semanticData->getPropertyValues( $dataItem );
				$updatejobflag = !$this->equalDatavalues( $oldValues, $newValues );
			}
		}

		// Job generation
		if ( $updatejobflag ) {
			$prop = new SMWDIProperty( $this->title->getDBkey() );

			// Array of all subjects that have some value for the given property
			$subjects = $store->getAllPropertySubjects( $prop );

			// Add jobs
			$this->addJobs( $subjects, $jobs );

			// Hook
			wfRunHooks( 'smwUpdatePropertySubjects', array( &$jobs ) );

			// Fetch all those that have an error property attached and
			// re-run it through the job-queue
			$subjects = $store->getPropertySubjects(
				new SMWDIProperty( SMWDIProperty::TYPE_ERROR ),
				$this->semanticData->getSubject()
			);

			// Add jobs
			$this->addJobs( $subjects, $jobs );
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Helper method to handle diff/change of conversion related properties
	 *
	 * @note if it is a type we need to check if the conversion factors
	 * have been changed
	 *
	 * @since 1.9
	 *
	 * @param SMWStore $store
	 * @param array &$jo
	 */
	protected function getDiffConversionFactors( SMWStore $store, array &$jobs ) {
		wfProfileIn( __METHOD__ );

		$updatejobflag = false;
		$pconv = new SMWDIProperty( SMWDIProperty::TYPE_CONVERSION );
		$ptype = new SMWDIProperty( SMWDIProperty::TYPE_HAS_TYPE );

		$oldfactors = smwfGetStore()->getPropertyValues(
			$this->semanticData->getSubject(),
			$pconv
		);
		$newfactors = $this->semanticData->getPropertyValues( $pconv );

		// Compare
		$updatejobflag = !$this->equalDatavalues( $oldfactors, $newfactors );

		// Job generation
		if ( $updatejobflag ) {

			/// FIXME: this will kill large wikis! Use incremental updates!
			$dataValue = SMWDataValueFactory::newTypeIdValue( '__typ', $title->getDBkey() );
			$propertyPages = $store->getPropertySubjects( $ptype, $dataValue );

			foreach ( $propertyPages as $propertyPage ) {
				// Add jobs
				$this->addJobs( array( $propertyPage ), $jobs );

				$prop = new SMWDIProperty( $propertyPage->getDBkey() );
				$subjects = $store->getAllPropertySubjects( $prop );

				// Add jobs
				$this->addJobs( $subjects, $jobs );

				$subjects = $store->getPropertySubjects(
					new SMWDIProperty( SMWDIProperty::TYPE_ERROR  ),
					$prop->getWikiPageValue()
				);

				// Add jobs
				$this->addJobs( $subjects, $jobs );
			}
		}

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Helper method to iterate over an array of SMWDIWikiPage and return and
	 * array of SMWUpdateJob jobs
	 *
	 * Check whether a job with the same getPrefixedDBkey string (prefixed title,
	 * with underscores and any interwiki and namespace prefixes) is already
	 * registered and if so don't insert a new job. This is particular important
	 * for pages that include a large amount of subobjects where the same Title
	 * and ParserOutput object is used (subobjects are included using the same
	 * WikiPage which means the resulting ParserOutput object is the same)
	 *
	 * @since 1.9
	 *
	 * @param SMWDIWikiPage[] $subjects
	 * @param array &$jobs
	 */
	protected function addJobs( array $subjects, &$jobs ) {

		foreach ( $subjects as $subject ) {
			$duplicate = false;
			$subjectTitle = $subject->getTitle();

			if ( $subjectTitle instanceof Title ) {

				// Avoid duplicate jobs for the same title object
				foreach ( $jobs as $job ) {
					if ( $job->getTitle()->getPrefixedDBkey() === $subjectTitle->getPrefixedDBkey() ){
						$duplicate = true;
						break;
					}
				}
				if ( !$duplicate ) {
					$jobs[] = new SMWUpdateJob( $subjectTitle );
				}
			}
		}
	}

	/**
	 * Helper function that compares two arrays of data values to check whether
	 * they contain the same content. Returns true if the two arrays contain the
	 * same data values (irrespective of their order), false otherwise.
	 *
	 * @since  1.9
	 */
	protected function equalDatavalues( $oldDataValue, $newDataValue ) {
		// The hashes of all values of both arrays are taken, then sorted
		// and finally concatenated, thus creating one long hash out of each
		// of the data value arrays. These are compared.
		$values = array();
		foreach ( $oldDataValue as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$oldDataValueHash = implode( '___', $values );

		$values = array();
		foreach ( $newDataValue as $v ) {
			$values[] = $v->getHash();
		}

		sort( $values );
		$newDataValueHash = implode( '___', $values );

		return ( $oldDataValueHash == $newDataValueHash );
	}
}
