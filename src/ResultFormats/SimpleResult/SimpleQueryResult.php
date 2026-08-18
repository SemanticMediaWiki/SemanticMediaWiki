<?php

declare( strict_types = 1 );

namespace SMW\ResultFormats\SimpleResult;

use ParamProcessor\ProcessingResult;

/**
 * @since 3.2
 */
class SimpleQueryResult {

	private $subjects;
	private $processingResult;

	public function __construct( SubjectCollection $subjects, ProcessingResult $processingResult ) {
		$this->subjects = $subjects;
		$this->processingResult = $processingResult;
	}

	public function getSubjects(): SubjectCollection {
		return $this->subjects;
	}

	public function getParameters(): array {
		return $this->processingResult->getParameterArray();
	}

	public function getProcessingResult(): ProcessingResult {
		return $this->processingResult;
	}

}
