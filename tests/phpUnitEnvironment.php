<?php

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PHPUnitEnvironment {

	/**
	 * @var array
	 */
	private $gitHead = [];

	/**
	 * @var int
	 */
	private $firstColumnWidth = SMW_PHPUNIT_FIRST_COLUMN_WIDTH;

	/**
	 * @param array $args
	 *
	 * @return boolean
	 */
	public function hasDebugRequest( $args ) {
		return array_search( '--debug', $args ) || array_search( '--debug-tests', $args );
	}

	public function emptyDebugVars() {
		$GLOBALS['wgDebugLogGroups'] = [];
		$GLOBALS['wgDebugLogFile'] = '';
	}

	/**
	 * @return boolean
	 */
	public function enabledDebugLogs() {
		return $GLOBALS['wgDebugLogGroups'] !== [] || $GLOBALS['wgDebugLogFile'] !== '';
	}

	/**
	 * @return boolean|integer
	 */
	public function getXdebugInfo() {

		if ( extension_loaded( 'xdebug' ) &&
			 ( function_exists( 'xdebug_is_enabled' ) || function_exists( 'xdebug_info' ) ) ) {
			return phpversion( 'xdebug' );
		}

		return false;
	}

	/**
	 * @return boolean|string
	 */
	public function getIntlInfo() {

		if ( extension_loaded( 'intl' ) ) {
			return phpversion( 'intl' ) . ' / ' . INTL_ICU_VERSION;
		}

		return false;
	}

	/**
	 * @return boolean|string
	 */
	public function getPcreInfo() {
		return defined( 'PCRE_VERSION' ) ? PCRE_VERSION : false;
	}

	/**
	 * @return string
	 */
	public function getSiteLanguageCode() {
		return $GLOBALS['wgLanguageCode'];
	}

	/**
	 * @return string
	 */
	public function executionTime() {
		$dateTimeUtc = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
		return $dateTimeUtc->format( 'Y-m-d h:i' );
	}

	/**
	 * @param string $id
	 *
	 * @return array
	 */
	public function getVersion( $id, $extra = [] ) {

		$info = [];

		try {
			$store_info = json_encode( smwfGetStore()->getInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		} catch( \Wikimedia\Rdbms\DBConnectionError $e ) {
			$store_info = 'No connection';
		}

		if ( $id === 'smw' ) {
			$info = [
				SMW_VERSION,
				'git: ' . $this->getGitInfo( 'smw' )
			] + $extra;
		}

		if ( $id === 'store' ) {
			$store = str_replace(
				[ '{', '}', '"', '(SMW', ':(', '))', ',' ],
				[ '(', ')', '', 'SMW', ' (', ')', ', ' ],
				$store_info
			);

			$info = [
				$store
			] + $extra;
		}

		if ( $id === 'mw' ) {
			$info = [
				MW_VERSION,
				'git: ' . $this->getGitInfo( 'mw' )
			] + $extra;
		}

		return implode( ', ', $info );
	}

	/**
	 * @param string $id
	 *
	 * @return string
	 */
	public function getGitInfo( $id ) {

		if ( $this->gitHead === [] && class_exists( 'GitInfo' ) ) {
			$this->gitHead = [
				'mw' => '',
				'smw' => ''
			];

			$this->gitHead['mw'] = GitInfo::headSHA1();

			if ( $this->gitHead['mw'] ) {
				$this->gitHead['mw'] = substr( $this->gitHead['mw'], 0, 7 );
			} elseif ( $this->command_exists( 'git' ) ) {
				// The download of a zip package will not provide any git sha1
				// reference therefore try to fetch it from github; `MW` is
				// exported by the Travis-CI environment to point to the selected
				// release/branch
				$refs = ( $env = getenv( 'MW' ) ) ? "refs/heads/$env" : "refs/tags/" . MW_VERSION;
				$output = null;

				if ( defined( 'SMW_PHPUNIT_PULL_VERSION_FROM_GITHUB' ) && SMW_PHPUNIT_PULL_VERSION_FROM_GITHUB ) {
					exec( "git ls-remote https://github.com/wikimedia/mediawiki $refs", $output );
				}

				$this->gitHead['mw'] = isset( $output[0] ) ? substr( $output[0], 0, 7 ) . " ($refs)"  : 'n/a';
			} else {
				$this->gitHead['mw'] = 'N/A';
			}

			$gitInfo = new GitInfo( __DIR__ . '/..' );
			$this->gitHead['smw'] = $gitInfo->getHeadSHA1();

			if ( $this->gitHead['smw'] ) {
				$this->gitHead['smw'] = substr( $this->gitHead['smw'], 0, 7 );
			}
		}

		if ( isset( $this->gitHead[$id] ) ) {
			return $this->gitHead[$id];
		}
	}

	/**
	 * @param string $arg1
	 * @param string|array $arg2
	 *
	 * @return string
	 */
	public function writeLn( $arg1, $arg2 ) {
		return print sprintf( "%-{$this->firstColumnWidth}s%s\n", $arg1, $arg2 );
	}

	/**
	 * @param string $arg1
	 * @param string|array $arg2
	 *
	 * @return string
	 */
	public function writeNewLn( $arg1 = '', $arg2 = '' ) {

		if ( $arg1 === '' && $arg2 === '' ) {
			return print "\n";
		}

		return print sprintf( "\n%-{$this->firstColumnWidth}s%s\n", $arg1, $arg2 );
	}

	private function command_exists( $command ) {
		$isWin = strtolower( substr( PHP_OS, 0, 3 ) ) === 'win';

		$spec = [
			[ "pipe", "r" ],
			[ "pipe", "w" ],
			[ "pipe", "w" ]
		];

		$proc = proc_open( ( $isWin ? 'where' : 'which' ) . " $command", $spec, $pipes );

		if ( is_resource( $proc ) ) {
			$stdout = stream_get_contents( $pipes[1] );
			$stderr = stream_get_contents( $pipes[2] );

			fclose( $pipes[1] );
			fclose( $pipes[2] );

			proc_close( $proc );
			return $stdout != '';
		}

		return false;
	}

}
