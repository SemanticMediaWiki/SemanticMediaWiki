<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultProfileNotFoundException;
use SMW\MediaWiki\Specials\FacetedSearch\Exception\ProfileSourceDefinitionConflictException;
use SMW\Schema\Compartment;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Schema\SchemaFactory;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class Profile {

	/**
	 * Schema type
	 */
	const SCHEMA_TYPE = 'FACETEDSEARCH_PROFILE_SCHEMA';

	private ?Compartment $profile = null;

	private array $profileList = [];

	private ?Compartment $defaultProfile = null;

	private string $profileName = '';

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly SchemaFactory $schemaFactory,
		string $profileName = '',
	) {
		$this->profileName = str_replace( '_profile', '', $profileName );
	}

	/**
	 * @since 3.2
	 */
	public function getProfileName(): string {
		return $this->profileName;
	}

	/**
	 * @since 3.2
	 */
	public function getProfileCount(): int {
		return count( $this->getProfileList() );
	}

	/**
	 * @since 3.2
	 */
	public function getProfileList(): array {
		if ( $this->profileList === [] ) {
			$this->loadProfile();
		}

		return $this->profileList;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return array|string|int
	 */
	public function get( string $key, $default = null ) {
		if ( $this->profile === null ) {
			$this->loadProfile();
		}

		$res = $this->profile->get( $key, null );

		if ( $res !== null ) {
			return $res;
		}

		return $this->defaultProfile->get( $key, $default );
	}

	private function loadProfile(): void {
		$schemaList = $this->schemaFactory->newSchemaFinder()->getSchemaListByType(
			self::SCHEMA_TYPE
		);

		if ( $schemaList === null ) {
			throw new SchemaTypeNotFoundException( self::SCHEMA_TYPE );
		}

		$compartmentIterator = $schemaList->newCompartmentIteratorByKey( 'profiles' );

		if ( $this->profileName === '' ) {
			$this->profileName = str_replace( '_profile', '', $schemaList->get( 'default_profile', 'default' ) ?? '' );
		}

		foreach ( $compartmentIterator as $profiles ) {
			foreach ( $profiles as $profile ) {
				$this->addProfile( $profile );
			}
		}

		if ( !$this->defaultProfile instanceof Compartment ) {
			throw new DefaultProfileNotFoundException();
		}
	}

	private function addProfile( Compartment $profile ): void {
		$name = str_replace( '_profile', '', $profile->get( Compartment::ASSOCIATED_SECTION ) );

		$this->profileList[$name] = $profile->get( 'message_key' );

		if ( $name === 'default' ) {
			$this->defaultProfile = $profile;
		}

		if ( $name !== $this->profileName ) {
			return;
		}

		if ( $this->profile !== null ) {
			throw new ProfileSourceDefinitionConflictException(
				$name,
				$this->profile->get( Compartment::ASSOCIATED_SCHEMA ),
				$profile->get( Compartment::ASSOCIATED_SCHEMA )
			);
		}

		$this->profile = $profile;
	}

}
