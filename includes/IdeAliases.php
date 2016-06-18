<?php

/**
 * Indicate class aliases in a way PHPStorm and Eclipse understand.
 * This is purely an IDE helper file, and is not loaded by the extension.
 *
 * @since 1.9
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

throw new Exception( 'Not an actual source file' );

class SMWDataItemException extends SMW\DataItemException {
}

abstract class SMWStore extends SMW\Store {
}

class SMWSemanticData extends SMW\SemanticData {
}

class SMWDIWikiPage extends SMW\DIWikiPage {
}

class SMWDIConcept extends SMW\DIConcept {
}

class SMWDIProperty extends SMW\DIProperty {
}

class SMWDISerializer extends SMW\Serializers\QueryResultSerializer {
}

class SMWUpdateJob extends SMW\UpdateJob {
}

class SMWRefreshJob extends SMW\RefreshJob {
}

abstract class SMWResultPrinter extends SMW\ResultPrinter {
}

class SMWCategoryResultPrinter extends SMW\CategoryResultPrinter {
}

class SMWDSVResultPrinter extends SMW\DsvResultPrinter {
}

class SMWEmbeddedResultPrinter extends SMW\EmbeddedResultPrinter {
}

class SMWRDFResultPrinter extends SMW\RdfResultPrinter {
}

class SMWListResultPrinter extends SMW\ListResultPrinter {
}

interface SMWIResultPrinter extends SMW\QueryResultPrinter {
}

class SMWSparqlDatabase4Store extends SMW\SPARQLStore\FourstoreHttpDatabaseConnector {
}

class SMWSparqlDatabaseVirtuoso extends SMW\SPARQLStore\VirtuosoHttpDatabaseConnector {
}

class SMWSparqlStore extends SMW\SPARQLStore\SPARQLStore {
}

class SMWSparqlDatabase extends SMW\SPARQLStore\GenericHttpDatabaseConnector {
}
