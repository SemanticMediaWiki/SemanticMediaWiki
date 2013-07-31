<?php

namespace SMW;

use MWException;

/**
 * Exception to be thrown when data items are created from unsuitable inputs
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 */

/**
 * Exception to be thrown when data items are created from unsuitable inputs
 *
 * @ingroup Exception
 * @codeCoverageIgnore
 */
class DataItemException extends MWException {}

/**
 * SMWDataItemException
 *
 * @deprecated since SMW 1.9
 * @codeCoverageIgnore
 */
class_alias( 'SMW\DataItemException', 'SMWDataItemException' );
