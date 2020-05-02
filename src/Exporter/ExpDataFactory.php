<?php

namespace SMW\Exporter;

use SMWExpData as ExpData;
use SMWExporter as Exporter;
use SMW\Exporter\Element\ExpResource;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Site;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ExpDataFactory {

	/**
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * @since 3.2
	 *
	 * @param Exporter $exporter
	 */
	public function __construct( Exporter $exporter ) {
		$this->exporter = $exporter;
	}

	/**
	 * @since 3.2
	 *
	 * @return ExpData
	 */
	public function newSiteExpData() : ExpData {

		// assemble export data:
		$expData = new ExpData( new ExpResource( '&wiki;#wiki' ) );

		$expData->addPropertyObjectValue(
			$this->exporter->newExpNsResourceById( 'rdf', 'type' ),
			new ExpData( $this->exporter->newExpNsResourceById( 'swivt', 'Wikisite' ) )
		);

		// basic wiki information
		$expData->addPropertyObjectValue(
			$this->exporter->newExpNsResourceById( 'rdfs', 'label' ),
			new ExpLiteral( Site::name() )
		);

		$expData->addPropertyObjectValue(
			$this->exporter->newExpNsResourceById( 'swivt', 'siteName' ),
			new ExpLiteral( Site::name(), 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$expData->addPropertyObjectValue(
			$this->exporter->newExpNsResourceById( 'swivt', 'pagePrefix' ),
			new ExpLiteral( $this->exporter->expandURI( '&wikiurl;' ), 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$expData->addPropertyObjectValue(
			$this->exporter->newExpNsResourceById( 'swivt', 'smwVersion' ),
			new ExpLiteral( SMW_VERSION, 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$expData->addPropertyObjectValue(
			$this->exporter->newExpNsResourceById( 'swivt', 'langCode' ),
			new ExpLiteral( Site::languageCode(), 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$mainpage = Title::newMainPage();

		if ( $mainpage !== null ) {
			$ed = new ExpData( new ExpResource( $mainpage->getFullURL() ) );
			$expData->addPropertyObjectValue( $this->exporter->newExpNsResourceById( 'swivt', 'mainPage' ), $ed );
		}

		// statistical information
		foreach ( Site::stats() as $key => $value ) {
			$expData->addPropertyObjectValue(
				$this->exporter->newExpNsResourceById( 'swivt', $key ),
				new ExpLiteral( (string)$value, 'http://www.w3.org/2001/XMLSchema#int' )
			);
		}

		return $expData;
	}

	/**
	 * @since 3.2
	 *
	 * @return ExpData
	 */
	public function newDefinedExpData() : ExpData {

		// link to list of existing pages:
		// check whether we have title as a first parameter or in URL
		if ( strpos( $this->exporter->expandURI( '&wikiurl;' ), '?' ) === false ) {
			$nexturl = $this->exporter->expandURI( '&export;?offset=0' );
		} else {
			$nexturl = $this->exporter->expandURI( '&export;&amp;offset=0' );
		}

		$expData = new ExpData(
			new ExpResource( $nexturl )
		);

		$ed = new ExpData( $this->exporter->newExpNsResourceById( 'owl', 'Thing' ) );
		$expData->addPropertyObjectValue( $this->exporter->newExpNsResourceById( 'rdf', 'type' ), $ed );

		$ed = new ExpData( new ExpResource( $nexturl ) );
		$expData->addPropertyObjectValue( $this->exporter->newExpNsResourceById( 'rdfs', 'isDefinedBy' ), $ed );

		return $expData;
	}

	/**
	 * Create an SMWExpData container that encodes the ontology header for an
	 * SMW exported OWL file.
	 *
	 * @param string $ontologyuri specifying the URI of the ontology, possibly
	 * empty
	 *
	 * @return ExpData
	 */
	public function newOntologyExpData( string $ontologyuri ) : ExpData {

		$expData = new ExpData(
			new ExpResource( $ontologyuri )
		);

		$ed = $this->exporter->newExpNsResourceById( 'owl', 'Ontology' );
		$expData->addPropertyObjectValue( $this->exporter->newExpNsResourceById( 'rdf', 'type' ), $ed );

		$ed = new ExpLiteral( date( DATE_W3C ), 'http://www.w3.org/2001/XMLSchema#dateTime' );
		$expData->addPropertyObjectValue( $this->exporter->newExpNsResourceById( 'swivt', 'creationDate' ), $ed );

		$ed = new ExpResource( 'http://semantic-mediawiki.org/swivt/1.0' );
		$expData->addPropertyObjectValue( $this->exporter->newExpNsResourceById( 'owl', 'imports' ), $ed );

		return $expData;
	}


}
