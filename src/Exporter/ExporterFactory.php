<?php

namespace SMW\Exporter;

use SMW\Exporter\Serializer\Serializer;
use SMW\Exporter\Serializer\RDFXMLSerializer;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Exporter\Controller\Queue;
use SMWExportController as ExportController;
use SMWExporter as Exporter;
use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ExporterFactory {

	/**
	 * @since 3.2
	 *
	 * @return Exporter
	 */
	public function getExporter() : Exporter {
		return Exporter::getInstance();
	}

	/**
	 * @since 3.2
	 *
	 * @param Serializer $serializer
	 *
	 * @return ExportController
	 */
	public function newExportController( Serializer $serializer ) : ExportController {

		$exportController = new ExportController(
			$serializer,
			new Queue(),
			$this->newExpDataFactory( $this->getExporter() )
		);

		return $exportController;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 *
	 * @return Serializer
	 * @throws InvalidArgumentException
	 */
	public function newSerializerByType( string $type ) : Serializer {

		switch ( $type ) {
			case 'application/x-turtle':
			case 'turtle':
				return $this->newTurtleSerializer();
				break;
			case 'application/rdf+xml':
			case 'rdfxml':
				return $this->newRDFXMLSerializer();
				break;
		}

		throw new InvalidArgumentException( "$type is not matchable to a registered serializer!" );
	}

	/**
	 * @since 3.2
	 *
	 * @return RDFXMLSerializer
	 */
	public function newRDFXMLSerializer() : RDFXMLSerializer {
		return new RDFXMLSerializer();
	}

	/**
	 * @since 3.2
	 *
	 * @return TurtleSerializer
	 */
	public function newTurtleSerializer() : TurtleSerializer {
		return new TurtleSerializer();
	}

	/**
	 * @since 3.2
	 *
	 * @param Exporter $exporter
	 *
	 * @return ExpDataFactory
	 */
	public function newExpDataFactory( Exporter $exporter ) : ExpDataFactory {
		return new ExpDataFactory( $exporter );
	}

}
