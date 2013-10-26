<?php

/**
 * Indicate class aliases in a way PHPStorm and Eclipse understand.
 * This is purely an IDE helper file, and is not loaded by the extension.
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

throw new Exception( 'Not an actual source file' );

abstract class SMWResultPrinter extends SMW\ResultPrinter {}

class SMWDataItemException extends SMW\DataItemException {}

abstract class SMWStore extends SMW\Store {}

class SMWSemanticData extends SMW\SemanticData {}

class SMWDIWikiPage extends SMW\DIWikiPage {}

class SMWDIProperty extends SMW\DIProperty {}

class SMWDISerializer extends SMW\Serializers\QueryResultSerializer {}

class SMWUpdateJob extends SMW\UpdateJob {}

class SMWRefreshJob extends SMW\RefreshJob {}
