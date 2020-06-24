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

class SMWDataItemException extends SMW\Exception\DataItemException {
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

class SMWUpdateJob extends \SMW\MediaWiki\Jobs\UpdateJob {
}

class SMWRefreshJob extends \SMW\MediaWiki\Jobs\RefreshJob {
}

abstract class SMWResultPrinter extends SMW\ResultPrinter {
}

class SMWCategoryResultPrinter extends SMW\Query\ResultPrinters\CategoryResultPrinter {
}

class SMWDSVResultPrinter extends \SMW\Query\ResultPrinters\DsvResultPrinter {
}

class SMWEmbeddedResultPrinter extends \SMW\Query\ResultPrinters\EmbeddedResultPrinter {
}

class SMWRDFResultPrinter extends \SMW\Query\ResultPrinters\RdfResultPrinter {
}

class SMWListResultPrinter extends SMW\Query\ResultPrinters\ListResultPrinter {
}

interface SMWIResultPrinter extends SMW\QueryResultPrinter {
}

class SMWSparqlStore extends SMW\SPARQLStore\SPARQLStore {
}
