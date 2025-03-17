<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats\SimpleResult;

/**
 * @since 3.2
 */
class SubjectCollection {

	private $pages;

	public function __construct( Subject ...$subjects ) {
		$this->pages = $subjects;
	}

	/**
	 * @param Subject[] $subjects
	 * @return self
	 */
	public static function newFromArray( array $subjects ): self {
		$instance = new self();
		$instance->pages = $subjects;
		return $instance;
	}

	/**
	 * @return Subject[]
	 */
	public function getSubjects(): array {
		return $this->pages;
	}

}
