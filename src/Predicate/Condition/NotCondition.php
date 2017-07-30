<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	class NotCondition extends Condition {
		protected $cond;

		public function __construct(array $subject) {
			$this->cond = Condition::fromSpecifiedArray($subject);
		}

		public function evaluate(array $dynamicData = []): bool {
			return !$this->cond->evaluate($dynamicData);
		}

		public function getDynamicIdentifiers(): array {
			return $this->cond->getDynamicIdentifiers();
		}

		function jsonSerialize() {
			return [
				'operator'	=>	'NOT',
				'operands'	=>	[ $this->cond ]
			];
		}
	}