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
	 * @param array $args
	 *
	 * @return boolean
	 */
	public function hasDebugRequest( $args ) {
		return array_search( '--debug', $args ) || array_search( '--debug-tests', $args );
	}

	public function emptyDebugVars() {
		$GLOBALS['wgDebugLogGroups'] = array();
		$GLOBALS['wgDebugLogFile'] = '';
	}

	/**
	 * @return boolean
	 */
	public function enabledDebugLogs() {
		return $GLOBALS['wgDebugLogGroups'] !== array() || $GLOBALS['wgDebugLogFile'] !== '';
	}

	/**
	 * @return boolean|integer
	 */
	public function getXdebugInfo() {

		if ( extension_loaded( 'xdebug' ) && xdebug_is_enabled() ) {
			return phpversion( 'xdebug' );
		}

		return false;
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
	public function getVersion( $id ) {

		if ( $id === 'smw' ) {
			return [
				SemanticMediaWiki::getVersion(),
				$this->getGitInfo( 'smw' ),
				str_replace( '\\\\', '\\', json_encode( SemanticMediaWiki::getEnvironment(), JSON_UNESCAPED_SLASHES ) )
			];
		}

		if ( $id === 'mw' ) {
			return [
				$GLOBALS['wgVersion'],
				$this->getGitInfo( 'mw' )
			];
		}
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
	 * @param string $bl
	 * @param string $arg1
	 * @param string|array $arg2
	 * @param string $al
	 *
	 * @return string
	 */
	public function writeLn( $bl, $arg1, $arg2, $al ) {

		if ( is_array( $arg2 ) ) {
			$arg2 = implode( ', ', array_values( $arg2 ) );
		}

		return print sprintf( "$bl%-20s%s$al", $arg1, $arg2 );
	}

}
