<?php
/**
 * This is an implementation of the SMW store that still uses the new
 * SMW SQL 2 Store for everything SMW does, but it decorates all edits to
 * the store with calls to a RAP store, so it keeps in parallel a second
 * store with all the semantic data. This allows for a SPARQL endpoint.
 *
 * @todo It is not good that SMW_RAPStore2 is basically
 * a copy of SMW_RAPStore. The architecture of these two stores need to be
 * rethought so that code duplication can be avoided.
 *
 * @author Denny Vrandecic (V. 0.1)
 * @author Felix Kratzer (V. 0.2)
 * @file
 * @ingroup SMWStore
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $smwgRAPPath;

define( 'RDFAPI_INCLUDE_DIR', $smwgRAPPath );
require_once( "$smwgRAPPath/RdfAPI.php" );

/**
 * Storage access class for using RAP as a triple store.
 * Most of the functions are simply forwarded to the SQL store.
 * @ingroup SMWStore
 */
class SMWRAPStore2 extends SMWSQLStore2 {
	protected $sqlstore;
	protected $rapstore;
	protected $modeluri;
	protected $baseuri;

	/**
	 * @todo Maybe find a better nomenclature for the model.
	 */
	public function SMWRAPStore2() {
		global $smwgRAPPath, $wgServer;


		$this->modeluri = SMWExporter::expandURI( $wgServer . "/model" );
		$this->baseuri  = SMWExporter::expandURI( $wgServer . "/id" );
	}

///// Writing methods /////

	function deleteSubject( Title $subject ) {

		// Translate SMWSemanticData to a RAP Model
		$rdfmodel = $this->getRAPModel();

		$rapsub = new Resource( SMWExporter::expandURI( $this->getURI( $subject ) ) );
		$this->removeSubjectFromRAP( $rdfmodel, $rapsub );

		return parent::deleteSubject( $subject );
	}

	function updateData( SMWSemanticData $data ) {
		// Create a local memmodel
		$model = ModelFactory::getDefaultModel();

		// Get DB-Model
		$rdfmodel = $this->getRAPModel();

		$ed = SMWExporter::makeExportData( $data ); // ExpData

		// Delete all we know about the subject!
		$rapsub = new Resource( SMWExporter::expandURI( $ed->getSubject()->getName() ) );
		$this->removeSubjectFromRAP( $rdfmodel, $rapsub );

		$tl = $ed->getTripleList(); // list of tenary arrays

		// Temporary List of all Blank Nodes in this dataobject
		$blankNodes = array();

		foreach ( $tl as $triple ) {
			$s = $triple[0]->getName();	// Subject
			$p = $triple[1]->getName();	// Predicate
			$o = $triple[2]->getName(); // Object


			// -------------------------------------------------------------------
			// Subject
			// -------------------------------------------------------------------
			$rap_subj = new Resource( SMWExporter::expandURI( $triple[0]->getName() ) );
			if ( $triple[0] instanceof SMWExpLiteral ) { }		// Should NEVER happen
			elseif ( $triple[0] instanceof SMWExpResource ) { }	// Nothing to do
			else {
				// Is this a blank node??
				if ( substr( $triple[0]->getName(), 0, 1 ) === "_" ) {
					// We need to create our own unique IDs as we cannot load the whole model into mem every time
					// The exporter generates Numbers inside the page so $triple[0]->getName() is unique on the page
					// $ed->getSubject()->getName() is unique for the wiki
					// we use md5 to get a nicer number, but we could use any other hashing method!
					//
					// Denny thinks this might be a bug of RAP... We leave it this way till we know better!
					//
					$bNodeId = '_' . md5( $ed->getSubject()->getName() . $triple[0]->getName() );
					$rap_subj = $blankNodes[$bNodeId];
				}
			}

			// -------------------------------------------------------------------
			// Predicate
			// -------------------------------------------------------------------
			$rap_pred = new Resource( SMWExporter::expandURI( $triple[1]->getName() ) );

			// -------------------------------------------------------------------
			// Object
			// -------------------------------------------------------------------
			$rap_obj  = new Resource( SMWExporter::expandURI( $triple[2]->getName() ) );
			if ( $triple[2] instanceof SMWExpLiteral ) {
				// This is a literal so get the correct type
				$rap_obj = new Literal( $triple[2]->getName() );
				$rap_obj->setDatatype( $triple[2]->getDatatype() );
			}
			elseif ( $triple[2] instanceof SMWExpResource ) { } // Nothing else to do
			else {
				// Is this a blank node??
				if ( substr( $triple[2]->getName(), 0, 1 ) === "_" ) {
					// See comment @Subject part about IDs
					$bNodeId = '_' . md5( $ed->getSubject()->getName() . $triple[2]->getName() );
					$rap_obj = new BlankNode( $bNodeId );
					$blankNodes[$bNodeId] = $rap_obj;
				}
			}

			// now add the new Statement
			$statement = new Statement( $rap_subj, $rap_pred, $rap_obj );
			$model->add( $statement );
		}

		// Add the mem-model to the store
		$rdfmodel->addModel( $model );


		// Close connections
		$model->close();
		$rdfmodel->close();
		$this->closeRAP();


		return parent::updateData( $data );
	}

	function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {

		// Save it in parent store now!
		// We need that so we get all information correctly!
		$result = parent::changeTitle( $oldtitle, $newtitle, $pageid, $redirid );

		// Delete the old stuff
		$nameOld = SMWExporter::expandURI( $this->getURI( $oldtitle ) );
		$rdfmodel = $this->getRAPModel();
		$rapsubold = new Resource( $nameOld );
		$this->removeSubjectFromRAP( $rdfmodel, $rapsubold );

		$newpage = SMWDataValueFactory::newTypeIDValue( '_wpg' );
		$newpage->setValues( $newtitle->getDBkey(), $newtitle->getNamespace(), $pageid );
		$semdata = $this->getSemanticData( $newpage );
		$this->updateData( $semdata, false );

		// Save the old page
		$oldpage = SMWDataValueFactory::newTypeIDValue( '_wpg' );
		$oldpage->setValues( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $redirid );
		$semdata = $this->getSemanticData( $oldpage );
		$this->updateData( $semdata, false );

		return $result;
	}

///// Setup store /////

	/**
	 * Setup all storage structures properly for using the store. This function performs tasks like
	 * creation of database tables. It is called upon installation as well as on upgrade: hence it
	 * must be able to upgrade existing storage structures if needed. It should return "true" if
	 * successful and return a meaningful string error message otherwise.
	 *
	 * The parameter $verbose determines whether the procedure is allowed to report on its progress.
	 * This is doen by just using print and possibly ob_flush/flush. This is also relevant for preventing
	 * timeouts during long operations. All output must be valid XHTML, but should preferrably be plain
	 * text, possibly with some linebreaks and weak markup.
	 */
	function setup( $verbose = true ) {
		$this->reportProgress( "Opening connection to DB for RAP ...\n", $verbose );
		$rdfstore = $this->getRAPStore();
		$this->reportProgress( "Check if DB schema is already set up for RAP ...\n", $verbose );
		if ( $rdfstore->isSetup( 'MySQL' ) ) {
			$this->reportProgress( "RAP DB schema is already set up.\n", $verbose );
		} else {
			$this->reportProgress( "Creating DB schema for RAP ...\n", $verbose );
 			$rdfstore->createTables( 'MySQL' ); // TODO MySQL specific
			$this->reportProgress( "RAP DB schema created.\n", $verbose );
		}
		$this->reportProgress( "Checking RAP model...\n", $verbose );
		if ( $rdfstore->modelExists( $this->modeluri ) ) {
			$this->reportProgress( "RAP model exiists.\n", $verbose );
		} else {
			$this->reportProgress( "Creating RAP model...\n", $verbose );
			$rdfstore->getNewModel( $this->modeluri, $this->baseuri );
			$this->reportProgress( "Created RAP model $this->modeluri\n", $verbose );
		}
		$this->closeRAP();
		$this->reportProgress( "RAP setup finished. Handing over to SQL store setup.\n\n", $verbose );
		return parent::setup( $verbose );
	}

	function drop( $verbose = true ) {
		/// TODO: undo all DB changes introduced by setup()
		/// Well, not all, just delete the created model. The database tables must retain, since
		/// there are only one set of tables for several models.
		return parent::drop();
	}

	/**
	 * Returns the connection to the RAP Database store. As of now, only MySQL
	 * is supported.
	 * @todo Allow other DBs.
	 */
	protected function getRAPStore() {
		// TODO only for MySQL, check for other databases!
		// Also, RAP ignores prefixes for tables. Bad RAP. Need to check with
		// the RAP developers to change that.
		global $smwgRapDBserver, $smwgRapDBname, $smwgRapDBuser, $smwgRapDBpassword;
		$this->rapstore = ModelFactory::getDbStore( 'MySQL', $smwgRapDBserver, $smwgRapDBname, $smwgRapDBuser, $smwgRapDBpassword );
		return $this->rapstore;
	}

	/**
	 * Returns the actual model where all the triples are saved.
	 */
	protected function getRAPModel() {
		$rapstore = $this->getRAPStore();
		return $rapstore->getModel( $this->modeluri );
	}

	/**
	 * Closes the connection to the RAP DB. As of now, this is disabled since it
	 * seems to close the connection to the MW DB as well (probably, because it
	 * is the same DB...).
	 * @todo Check if this can possibly lead to a resource leak.
	 */
	protected function closeRAP() {
		// $this->rapstore->close();
	}

///// Additional helpers
	/**
	* Deletes all relations for the given subject from RAP.
	* This especially also handles n-ary relations recursevly as we would lose them
	**/
	protected function removeSubjectFromRAP( $rdfmodel, Resource $subject ) {
		$oldmodel = $rdfmodel->find( $subject, null, null );
		$i = $oldmodel->getStatementIterator();
		$i->moveFirst();
		while ( $i->current() != null ) {
			$stmt = $i->current();

			$rdfmodel->remove( $stmt );

			$obj = $stmt->object();
			if ( $obj instanceof BlankNode ) {
				// It's a blank node in the object, this means a n-ary relation has been saved
				// So delete everything for this blank node as well!
				$this->removeSubjectFromRAP( $rdfmodel, $obj );
			}

			$i->next();
		}
		// TODO Delete More Stuff, if we save more stuff
	}

	/**
	 * Having a title of a page, what is the URI that is described by that page?
	 *
	 * The result still requires expandURI()
	 */
	protected function getURI( $title ) {
		$uri = "";
		if ( $title instanceof Title ) {
			$dv = SMWDataValueFactory::newTypeIDValue( '_wpg' );
			$dv->setTitle( $title );
			$exp = $dv->getExportData();
			$uri = $exp->getSubject()->getName();
		} else {
			// There could be other types as well that we do NOT handle here
		}

		return $uri; // still requires expandURI()
	}
}

