<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	abstract class HigherOrderCondition extends Condition {
		protected $nestedConditions;

		public function __construct(array ...$conditions) {
			parent::__construct($conditions);
			$this->nestedConditions = array_map([Condition::class, 'fromSpecifiedArray'], $conditions);
		}

		public function getDynamicOperands(): array {
			return array_map(function (Condition $cond) {
				return $cond->getDynamicOperands();
			}, $this->nestedConditions);
		}

		public function writeOperandCache(array $dynData) {
			array_map(function (Condition $cond, array $cache) {
				$cond->writeOperandCache($cache);
			}, $this->nestedConditions, $dynData);
		}

		protected function getCacheSpecification(): array {
			return [
				'__root' . $this->getArityModifier()	=>	Condition::BASE_SPECIFICATION
			];
		}

		protected function getArityModifier(): string {
			return '+';
		}
	}