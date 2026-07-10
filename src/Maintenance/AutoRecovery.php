<?php

namespace SMW\Maintenance;

use MediaWiki\MediaWikiServices;
use SMW\DatabaseMetaRepo;

/**
 * Persists a resumable checkpoint for long-running maintenance scripts (e.g.
 * `rebuildData`, `rebuildElasticIndex`) run with `--auto-recovery`, so an
 * aborted run can restart from the last processed entity id.
 *
 * Since 7.0.0 the install-state metadata that used to live in the `.smw.json`
 * file is stored in the `smw_meta` database table. The auto-recovery checkpoint
 * now lives there too, in its own row per script identifier
 * (`{@see self::TOPIC_IDENTIFIER}.<identifier>`) that {@see \SMW\DatabaseMetaRepo}
 * keeps out of the install-state slice. This means `--auto-recovery` no longer
 * depends on a writable `$smwgConfigFileDir` / `.smw.json` file (#7030); reads
 * never write, the per-entity checkpoint is a single-row upsert that cannot
 * touch install-state keys, and because each script owns its own row, running
 * two rebuild scripts concurrently cannot clobber each other's checkpoint.
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class AutoRecovery {

	/**
	 * Reserved `smw_meta` key prefix; the checkpoint is stored one row per
	 * script identifier at `TOPIC_IDENTIFIER . '.' . $identifier`. See
	 * {@see \SMW\DatabaseMetaRepo}'s reserved-key handling.
	 */
	const TOPIC_IDENTIFIER = 'maintenance_script.auto_recovery';

	private bool $enabled = false;

	private int $safeMargin = 0;

	/**
	 * This identifier's checkpoint payload shaped as `[ key => value ]`. Loaded
	 * once from `smw_meta` on first access and mirrored back on every `set`.
	 */
	private array $contents = [];

	private bool $loaded = false;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly string $identifier,
		private ?DatabaseMetaRepo $repo = null
	) {
	}

	/**
	 * @since 3.1
	 */
	public function enable( bool $enabled ): void {
		$this->enabled = $enabled;
	}

	/**
	 * @since 3.1
	 */
	public function safeMargin( int $safeMargin ): void {
		$this->safeMargin = $safeMargin;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( string $key, $value ): bool {
		if ( !$this->enabled ) {
			return false;
		}

		$this->initContents( $key );

		$this->contents[$key] = $value;

		$this->getRepo()->writeValue( $this->metaKey(), $this->contents );

		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @return mixed
	 */
	public function get( string $key ) {
		if ( !$this->enabled ) {
			return false;
		}

		$this->initContents( $key );

		$value = $this->contents[$key];

		if ( is_int( $value ) ) {
			return max( 0, $value - $this->safeMargin );
		}

		return $value;
	}

	/**
	 * @since 3.1
	 */
	public function has( string $key ): bool {
		if ( !$this->enabled ) {
			return false;
		}

		$this->initContents( $key );

		return $this->contents[$key] !== false;
	}

	private function initContents( string $key ): void {
		if ( !$this->loaded ) {
			$stored = $this->getRepo()->readValue( $this->metaKey() );
			$this->contents = is_array( $stored ) ? $stored : [];
			$this->loaded = true;
		}

		if ( !isset( $this->contents[$key] ) ) {
			$this->contents[$key] = false;
		}
	}

	private function metaKey(): string {
		return self::TOPIC_IDENTIFIER . '.' . $this->identifier;
	}

	private function getRepo(): DatabaseMetaRepo {
		if ( $this->repo === null ) {
			$this->repo = new DatabaseMetaRepo(
				MediaWikiServices::getInstance()->getDBLoadBalancer()
			);
		}

		return $this->repo;
	}

}
