<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	abstract class StringCondition extends Condition {
		public function __construct(string $needle, string $haystack) {
			parent::__construct([
				'needle'	=>	$needle,
				'haystack'	=>	$haystack
			]);
		}

		public function getCacheSpecification(): array {
			return [
				'needle'	=>	'string',
				'haystack'	=>	'string'
			];
		}
	}