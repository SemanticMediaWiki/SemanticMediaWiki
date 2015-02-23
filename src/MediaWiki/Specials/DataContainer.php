<?php

namespace SMW\MediaWiki\Specials;

use SMW\ExternalDataContainer;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\Subobject;
use SMW\DIWikiPage;
use SMW\DIProperty;

use SpecialPage;

/**
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class DataContainer extends SpecialPage {

	/**
	 * http://www.ferc.gov/docs-filing/eqr/soft-tools/sample-csv/transaction.txt
	 */
	private $csvSampleData = 'transaction_unique_identifier,seller_company_name,customer_company_name,customer_duns_number,tariff_reference,contract_service_agreement,trans_id,transaction_begin_date,transaction_end_date,time_zone,point_of_delivery_control_area,specific location,class_name,term_name,increment_name,increment_peaking_name,product_name,transaction_quantity,price,units,total_transmission_charge,transaction_charge
T1,The Electric Company,"The Electric Marketing Co., LLC",23456789,FERC Electric Tariff Original Volume No. 2,Service Agreement 1,8700,200401010000,200403312359,ES,PJM,BUS 4321,UP,LT,Y,FP,ENERGY,22574,39,$/MWH,0,880386
T2,The Electric Company,The Power Company,45653333,FERC Electric Tariff Original Volume No. 10,2,8701,200401010000,200402010000,CS,DPL,Green Sub Busbar,F,ST,M,FP,ENERGY,16800,32,$/MWH,0,537600
T3,The Electric Company,The Power Company,45653333,FERC Electric Tariff Original Volume No. 10,2,8702,200402010000,200403010000,CS,DPL,Green Sub Busbar,F,ST,M,FP,ENERGY,16800,32,$/MWH,0,537600';

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'DataContainer' );
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$output = $this->getOutput();
		$output->setPageTitle( $this->msg( 'datacontainer' )->text() );

		$externalDataContainer = new ExternalDataContainer( 'www.ferc.gov' );
		$externalDataContainer->setDataGroup( 'csv.transaction' );

		// We just delete any data before the demo
		$this->applicationFactory->getStore()->deleteSubject(
			$externalDataContainer->getSubject()->getTitle()
		);

		foreach ( $this->parseCsv( $this->csvSampleData ) as $line ) {
			$this->addValuesToExternalDataContainer(
				$externalDataContainer,
				$line
			);
		}

		$this->applicationFactory->getStore()->updateData(
			$externalDataContainer->getSemanticData()
		);

		$count = count( $this->applicationFactory->getStore()->getSemanticData(
			$externalDataContainer->getSubject() )->getPropertyValues( new DIProperty( '_SOBJ' )
		) );

		$output->addHTML( '<div><b>A demonstration on how to add external data to the storage engine without requiring a wikipage.</b></div>' );
		$output->addHTML( 'Number of added subobjects: ' . $count );
	}

	private function addValuesToExternalDataContainer( $externalDataContainer, array $data ) {

		$externalDataContainer->setEmptyContainerForDataIdentifier( sha1( json_encode( $data ) ) );
		$subject = $externalDataContainer->getSubject();

		foreach ( $data as $property => $value ) {

			$dataValue = $this->dataValueFactory->newPropertyValue(
					$property,
					$value,
					false,
					$subject
				);

			$externalDataContainer->addDataValueToContainer( $dataValue );
		}

		$externalDataContainer->copyContainerToSemanticData();
	}

	private function parseCsv( $csv, $delimiter = ',' ) {

		$limit = 5 * 1024 * 1024; // 5MB;
		$handle = fopen( "php://temp/maxmemory:$limit", 'r+' );
		fputs( $handle, $csv );
		rewind( $handle );

		$header = null;
		$data = array();

		while ( ( $row = fgetcsv( $handle, 1000, $delimiter ) ) !== false ) {
			if ( !$header ) {
				$header = $row;
			} else {
				$data[] = array_combine( $header, $row);
			}
		}

		fclose( $handle );

		return $data;
	}

}
