<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	class NotCondition extends HigherOrderCondition {
		public function evaluate(): bool {
			if ($cond = $this->nestedConditions[0]) {
				return $cond instanceof Condition && !$cond->evaluate();
			}

			return false;
		}

		protected function getArityModifier(): string {
			return '{1}';
		}
	}