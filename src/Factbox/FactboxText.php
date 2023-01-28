<?php

namespace SMW\Factbox;

class FactboxText {

	private ?string $text = null;

	public function clear(): void {
		$this->text = null;
	}

	public function setText( ?string $text ): void {
		$this->text = $text;
	}

	public function getText(): ?string {
		return $this->text;
	}

	public function hasText(): bool {
		return $this->text !== null;
	}

	public function hasNonEmptyText(): bool {
		return $this->text !== null && $this->text !== '';
	}

}
