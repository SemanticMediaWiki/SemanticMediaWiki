<?php

namespace SMW\Tests;

use SMW\MediaWiki\Connection\Sequence;
use SMW\ApplicationFactory;

if ( !class_exists( '\PHPUnit_TextUI_Command' ) ) {
	class_alias( '\PHPUnit\TextUI\Command', '\PHPUnit_TextUI_Command' );
}

/**
 * @private
 *
 * Warp the standard PHPUnit_TextUI_Command to allow for running some clean up,
 * tear down at the very last. Common inception points are not eligible to only
 * run after all tests have been completed (or failed).
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PHPUnitCommand extends \PHPUnit_TextUI_Command {

	/**
	 * @see PHPUnit_TextUI_Command::main
	 *
	 * @param bool $exit
	 */
	public static function main( $exit = true ) {
		$command = new static;
		$return = $command->run( $_SERVER['argv'], false );

		$sequence = new Sequence(
			ApplicationFactory::getInstance()->getConnectionManager()->getConnection( 'mw.db' )
		);

		$sequence->tablePrefix( '' );
		$sequence->restart( \SMW\SQLStore\SQLStore::ID_TABLE, 'smw_id' );

		if ( $exit ) {
			exit( $return );
		}

		return $return;
	}

}
