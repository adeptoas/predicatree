<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	class StrEndsCondition extends StringCondition {
		public function evaluate(): bool {
			$needle = $this->arg('needle');
			$haystack = $this->arg('haystack');

			return strpos(strrev($haystack), strrev($needle)) === 0;
		}
	}