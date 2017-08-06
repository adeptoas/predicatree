<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	class StrBeginsCondition extends StringCondition {
		public function evaluate(): bool {
			$needle = $this->arg('needle');
			$haystack = $this->arg('haystack');

			return strpos($haystack, $needle) === 0;
		}
	}