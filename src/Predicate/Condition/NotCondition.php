<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	class NotCondition extends Condition {
		protected $cond;

		public function __construct(array $subject) {
			parent::__construct($subject);

			$this->cond = Condition::fromSpecifiedArray($subject);
		}

		public function evaluate(): bool {
			return !$this->cond->evaluate();
		}

		protected function op(string $key, $default = null) {
			return $this->cond->op($key, $default);
		}

		public function getDynamicOperands(): array {
			return $this->cond->getDynamicOperands();
		}

		public function writeOperandCache(array $dynData) {
			$this->cond->writeOperandCache($dynData);
		}

		protected function getCacheSpecification(): array {
			return Condition::BASE_SPECIFICATION;
		}
	}