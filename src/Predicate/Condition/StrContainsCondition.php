<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	class StrContainsCondition extends StringCondition {
		public function evaluate(): bool {
			$needle = $this->op('needle');
			$haystack = $this->op('haystack');

			return strpos($haystack, $needle) !== false;
		}
	}