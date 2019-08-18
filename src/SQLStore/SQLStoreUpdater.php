<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMWDIBlob as DIBlob;
use SMW\Parameters;
use SMW\SQLStore\PropertyStatisticsTable;
use SMW\SQLStore\PropertyTableRowDiffer;
use Title;

/**
 * Class Handling all the write and update methods for SMWSQLStore3.
 *
 * @license GNU GPL v2+
 * @since 1.8
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 * @author mwjames
 */
class SQLStoreUpdater {

	/**
	 * The store used by this store writer.
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * @var SQLStoreFactory
	 */
	private $factory;

	/**
	 * @var PropertyTableRowDiffer
	 */
	private $propertyTableRowDiffer;

	/**
	 * @var PropertyTableUpdater
	 */
	private $propertyTableUpdater;

	/**
	 * @var SemanticDataLookup
	 */
	private $semanticDataLookup;

	/**
	 * @var IdChanger
	 */
	private $idChanger;

	/**
	 * @var RedirectUpdater
	 */
	private $redirectUpdater;

	/**
	 * @since 1.8
	 *
	 * @param SQLStore $store
	 * @param SQLStoreFactory $factory
	 */
	public function __construct( SQLStore $store, $factory ) {
		$this->store = $store;
		$this->factory = $factory;
		$this->propertyTableRowDiffer = $this->factory->newPropertyTableRowDiffer();
		$this->propertyTableUpdater = $this->factory->newPropertyTableUpdater();
		$this->semanticDataLookup = $this->factory->newSemanticDataLookup();
		$this->redirectUpdater = $this->factory->newRedirectUpdater();
	}

	/**
	 * @see SMWStore::deleteSubject
	 *
	 * @since 1.8
	 * @param Title $title
	 */
	public function deleteSubject( Title $title ) {

		// @deprecated since 2.1, use 'SMW::SQLStore::BeforeDeleteSubjectComplete'
		\Hooks::run( 'SMWSQLStore3::deleteSubjectBefore', [ $this->store, $title ] );

		\Hooks::run( 'SMW::SQLStore::BeforeDeleteSubjectComplete', [ $this->store, $title ] );

		// Fetch all possible matches (including any duplicates created by
		// incomplete rollback or DB deadlock)
		$idList = $this->store->getObjectIds()->findIdsByTitle(
			$title->getDBkey(),
			$title->getNamespace()
		);

		$extensionList = array_flip( $idList );
		$subject = DIWikiPage::newFromTitle( $title );

		$emptySemanticData = new SemanticData( $subject );
		$emptySemanticData->setOption( SemanticData::PROC_DELETE, true );

		$subobjectListFinder = $this->factory->newSubobjectListFinder();
		$propertyTableIdReferenceFinder = $this->store->service( 'PropertyTableIdReferenceFinder' );

		foreach ( $idList as $id ) {
			$this->doDelete( $id, $subject, $subobjectListFinder, $extensionList );
			$this->doDataUpdate( $emptySemanticData );

			if ( $propertyTableIdReferenceFinder->hasResidualPropertyTableReference( $id ) === false ) {
				// Mark subject/subobjects with a special IW, the final removal is being
				// triggered by the `EntityRebuildDispatcher`
				$this->store->getObjectIds()->updateInterwikiField(
					$id,
					$subject,
					SMW_SQL3_SMWDELETEIW
				);
			} else {
				// Convert the subject into a simple object instance
				$this->store->getObjectIds()->setPropertyTableHashes(
					$id,
					null
				);
			}
		}

		$extensionList = array_keys( $extensionList );

		$this->store->extensionData['delete.list'] = $extensionList;

		// @deprecated since 2.1, use 'SMW::SQLStore::AfterDeleteSubjectComplete'
		\Hooks::run( 'SMWSQLStore3::deleteSubjectAfter', [ $this->store, $title ] );

		\Hooks::run( 'SMW::SQLStore::AfterDeleteSubjectComplete', [ $this->store, $title ] );
	}

	private function doDelete( $id, $subject, $subobjectListFinder, &$extensionList ) {

		$this->semanticDataLookup->invalidateCache( $id );

		if ( $subject->getNamespace() === SMW_NS_CONCEPT ) { // make sure to clear caches
			$db = $this->store->getConnection();

			$db->delete(
				SQLStore::CONCEPT_TABLE,
				[ 's_id' => $id ],
				'SMW::deleteSubject::Conc'
			);

			$db->delete(
				SQLStore::CONCEPT_CACHE_TABLE,
				[ 'o_id' => $id ],
				'SMW::deleteSubject::Conccache'
			);
		}

		$subject->setId( $id );

		foreach( $subobjectListFinder->find( $subject ) as $subobject ) {
			$extensionList[$subobject->getId()] = true;

			$this->store->getObjectIds()->updateInterwikiField(
				$subobject->getId(),
				$subobject,
				SMW_SQL3_SMWDELETEIW
			);
		}
	}

	/**
	 * @see SMWStore::doDataUpdate
	 *
	 * @since 1.8
	 * @param SemanticData $data
	 */
	public function doDataUpdate( SemanticData $semanticData ) {

		// Deprecated since 3.1, use SMW::SQLStore::BeforeDataUpdateComplete
		\Hooks::run( 'SMWSQLStore3::updateDataBefore', [ $this->store, $semanticData ] );

		\Hooks::run( 'SMW::SQLStore::BeforeDataUpdateComplete', [
			$this->store,
			$semanticData
		] );

		$subject = $semanticData->getSubject();

		$connection = $this->store->getConnection( 'mw.db' );

		// MW 1.33+
		$connection->beginSectionTransaction( __METHOD__ );

		$subobjectListFinder = $this->factory->newSubobjectListFinder();

		$changeOp = $this->factory->newChangeOp(
			$subject
		);

		$this->propertyTableRowDiffer->setChangeOp(
			$changeOp
		);

		$changePropListener = $this->factory->newChangePropListener();
		$hierarchyLookup = $this->factory->newHierarchyLookup();

		// #2698
		$hierarchyLookup->addListenersTo(
			$changePropListener
		);

		$changePropListener->loadListeners(
			$this->store
		);

		if ( $semanticData->getOption( SemanticData::OPT_CHECK_REMNANT_ENTITIES ) ) {
			$this->propertyTableRowDiffer->checkRemnantEntities( true );
		}

		// Update data about our main subject
		$this->doFlatDataUpdate( $semanticData );
		$sid = $subject->getId();

		// Update data about our subobjects
		$subSemanticData = $semanticData->getSubSemanticData();
		$connection = $this->store->getConnection( 'mw.db' );

		foreach( $subSemanticData as $subobjectData ) {
			$this->doFlatDataUpdate( $subobjectData );
		}

		$deleteList = [];

		// Mark subobjects without reference to be deleted
		foreach( $subobjectListFinder->find( $subject ) as $subobject ) {
			if( !$semanticData->hasSubSemanticData( $subobject->getSubobjectName() ) ) {

				$this->doFlatDataUpdate( new SemanticData( $subobject ) );
				$deleteList[] = $subobject->getId();

				$this->store->getObjectIds()->updateInterwikiField(
					$subobject->getId(),
					$subobject,
					SMW_SQL3_SMWDELETEIW
				);
			}
		}

		if ( ( $rev_id = $semanticData->getExtensionData( 'revision_id' ) ) !== null ) {
			$this->store->getObjectIds()->updateRevField( $sid, $rev_id );
		}

		// Store the diff in cache so any post processing has a chance to find
		// what entities and values were changed
		$changeDiff = $changeOp->newChangeDiff();
		$changeDiff->save( ApplicationFactory::getInstance()->getCache() );

		$changePropListener->callListeners();

		$this->store->extensionData['delete.list'] = $deleteList;
		$this->store->extensionData['change.diff'] = $changeDiff;

		// Deprecated since 2.3, use SMW::SQLStore::AfterDataUpdateComplete
		\Hooks::run( 'SMWSQLStore3::updateDataAfter', [ $this->store, $semanticData ] );

		\Hooks::run( 'SMW::SQLStore::AfterDataUpdateComplete', [
			$this->store,
			$semanticData,
			$changeOp
		] );

		$connection->endSectionTransaction( __METHOD__ );
	}

	/**
	 * Update the store to contain the given data, without taking any
	 * subobject data into account.
	 *
	 * @since 1.8
	 * @param SemanticData $data
	 */
	protected function doFlatDataUpdate( SemanticData $data ) {
		$subject = $data->getSubject();

		// Take care of redirects
		$redirects = $data->getPropertyValues( new DIProperty( '_REDI' ) );

		if ( count( $redirects ) > 0 ) {
			$redirect = end( $redirects ); // at most one redirect per page
			$this->redirectUpdater->updateRedirects( $subject, $redirect );
			// Stop here:
			// * no support for annotations on redirect pages
			// * updateRedirects takes care of deleting any previous data
			return;
		} else {
			$this->redirectUpdater->updateRedirects( $subject );
		}

		// Find an approriate sortkey, the field is influenced by various
		// elements incl. DEFAULTSORT and can be altered without modifying
		// any other annotation.
		$sortKey = $this->makeSortKey( $subject, $data );

		// Always fetch an ID which is either recalled from cache or is created.
		// Doing so ensures that the sortkey and namespace data are updated
		// to both the DB and the cache.
		$sid = $this->store->getObjectIds()->makeSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName(),
			true,
			$sortKey,
			true
		);

		$subject->setSortKey( $sortKey );
		$subject->setId( $sid );

		// Find any potential duplicate entries for the current subject and
		// if matched, mark them as to be deleted
		$idList = $this->store->getObjectIds()->findIdsByTitle(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectName()
		);

		foreach ( $idList as $id ) {
			if ( $id != $sid ) {
				$this->store->getObjectIds()->updateInterwikiField(
					$id,
					$subject,
					SMW_SQL3_SMWDELETEIW
				);
			}
		}

		// Take care of all remaining property table data
		list( $insertRows, $deleteRows, $newHashes ) = $this->propertyTableRowDiffer->computeTableRowDiff(
			$sid,
			$data
		);

		$params = new Parameters(
			[
				'insert_rows' => $insertRows,
				'delete_rows' => $deleteRows,
				'new_hashes'  => $newHashes
			]
		);

		$this->propertyTableUpdater->update( $sid, $params );

		if ( $redirects === [] && $subject->getSubobjectName() === ''  ) {

			$dataItemFromId = $this->store->getObjectIds()->getDataItemById( $sid );

			// If for some reason the internal redirect marker is still set but no
			// redirect annotations are known then do update the interwiki field
			if ( $dataItemFromId !== null && $dataItemFromId->getInterwiki() === SMW_SQL3_SMWREDIIW ) {
				$this->store->getObjectIds()->updateInterwikiField( $sid, $subject );
			}
		}

		// Update caches (may be important if jobs are directly following this call)
		$this->semanticDataLookup->setLookupCache( $sid, $data );

		$this->store->getObjectIds()->setSequenceMap(
			$sid,
			$data->getSequenceMap()
		);
	}

	private function makeSortKey( $subject, $data ) {

		// Don't mind the delete process
		if ( $data->getOption( SemanticData::PROC_DELETE ) ) {
			return '';
		}

		$property = new DIProperty( '_SKEY' );

		// Take care of the sortkey
		$pv = $data->getPropertyValues( $property );
		$dataItem = end( $pv );

		if ( $dataItem instanceof DIBlob ) {
			$sortkey = $dataItem->getString();
		} elseif ( $data->getExtensionData( 'sort.extension' ) !== null ) {
			$sortkey = $data->getExtensionData( 'sort.extension' );
		} else {
			$sortkey = $subject->getSortKey();
		}

		// Extend the subobject sortkey in case no @sortkey was given for an
		// entity
		if ( $subject->getSubobjectName() !== '' && !$dataItem instanceof DIBlob ) {

			// Add sort data from some dedicated containers (of a record or
			// reference type etc.) otherwise use the sobj name as extension
			// to distinguish each entity
			if ( $data->getExtensionData( 'sort.data' ) !== null ) {
				$sortkey .= '#' . $data->getExtensionData( 'sort.data' );
			} else {
				$sortkey .= '#' . $subject->getSubobjectName();
			}
		}

		// #649 Be consistent about how sortkeys are stored therefore always
		// normalize even for usages like {{DEFAULTSORT: Foo_bar }}
		$sortkey = str_replace( '_', ' ', $sortkey );

		return $sortkey;
	}

	public function changeTitle( Title $oldTitle, Title $newTitle, $pageId, $redirectId = 0 ) {

		$options = [
			'page_id' => $pageId,
			'redirect_id' => $redirectId
		];

		$this->redirectUpdater->doUpdate(
			DIWikiPage::newFromTitle( $oldTitle ),
			DIWikiPage::newFromTitle( $newTitle ),
			$options
		);

		$this->redirectUpdater->invalidateLookupCache(
			$this->semanticDataLookup
		);

		$this->redirectUpdater->triggerChangeTitleUpdate(
			$oldTitle,
			$newTitle,
			$options
		);
	}

}
