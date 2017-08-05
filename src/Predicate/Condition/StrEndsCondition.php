<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	class StrEndsCondition extends StringCondition {
		public function evaluate(): bool {
			$needle = $this->op('needle');
			$haystack = $this->op('haystack');

			return strpos(strrev($haystack), strrev($needle)) === 0;
		}
	}