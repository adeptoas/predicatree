<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	class InCondition extends Condition {
		public function __construct($needle, $haystack, bool $strict = false) {
			parent::__construct([
				'needle'	=>	$needle,
				'haystack'	=>	$haystack,
				'strict'	=>	$strict
			]);
		}

		public function evaluate(): bool {
			$needle = $this->op('needle');
			$haystack = $this->op('haystack');

			$strict = $this->op('strict', false);

			return in_array($needle, $haystack, $strict);
		}

		protected function getCacheSpecification(): array {
			return [
				'needle'	=>	'any!',
				'haystack'	=>	'array::seq|string::__',
				'strict?'	=>	'bool'
			];
		}
	}