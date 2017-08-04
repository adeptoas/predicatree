<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	class AndCondition extends HigherOrderCondition {
		public function evaluate(): bool {
			return array_reduce($this->nestedConditions, function (bool $current, Condition $cond) {
				return $current && $cond->evaluate();
			}, true);
		}
	}