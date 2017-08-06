<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;
	use Adepto\PredicaTree\Predicate\HigherOrderCacheObject;

	class NotCondition extends Condition {
		use HigherOrderCacheObject;

		public function evaluate(): bool {
			if ($cond = $this->getChild(0)) {
				return $cond instanceof Condition && !$cond->evaluate();
			}

			return false;
		}

		protected function getArityModifier(): string {
			return '{1}';
		}
	}