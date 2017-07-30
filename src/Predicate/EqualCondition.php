<?php
	namespace Adepto\PredicaTree\Predicate;

	class EqualCondition extends Condition {
		protected $cmpThis;
		protected $cmpThat;

		protected $strict;

		public function __construct($cmpThis, $cmpThat, bool $strict = false) {
			$this->cmpThis = $cmpThis;
			$this->cmpThat = $cmpThat;

			$this->strict = $strict;
		}

		public function evaluate(array $dynamicData = []): bool {
			$cmpThis = $dynamicData['this'] ?? $this->cmpThis;
			$cmpThat = $dynamicData['that'] ?? $this->cmpThat;

			return $this->strict ? $cmpThis === $cmpThat : $cmpThis == $cmpThat;
		}

		public function getDynamicIdentifiers(): array {
			return [
				'this'	=>	$this->cmpThis,
				'that'	=>	$this->cmpThat
			];
		}

		function jsonSerialize() {
			return [
				'operator'	=>	'EQUAL',
				'operands'	=>	[ $this->cmpThis, $this->cmpThat, $this->strict ],
			];
		}
	}