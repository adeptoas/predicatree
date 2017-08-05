<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	class StrBeginsCondition extends StringCondition {
		public function evaluate(): bool {
			$needle = $this->op('needle');
			$haystack = $this->op('haystack');

			return strpos($haystack, $needle) === 0;
		}
	}