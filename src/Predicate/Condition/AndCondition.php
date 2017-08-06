<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;
	use Adepto\PredicaTree\Predicate\HigherOrderCacheObject;

	class AndCondition extends Condition {
		use HigherOrderCacheObject;

		public function evaluate(): bool {
			return array_reduce($this->getChildren(), function (bool $current, Condition $cond) {
				return $current && $cond->evaluate();
			}, true);
		}
	}