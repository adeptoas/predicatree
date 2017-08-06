<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	class EqualCondition extends Condition {
		public function __construct($cmpThis, $cmpThat, bool $strict = false) {
			parent::__construct([
				'this'		=>	$cmpThis,
				'that'		=>	$cmpThat,
				'strict'	=>	$strict
			]);
		}

		protected function getCacheSpecification(): array {
			return [
				'this'		=>	'any!',
				'that'		=>	'any!',
				'strict?'	=>	'bool'
			];
		}

		public function evaluate(): bool {
			$cmpThis = $this->arg('this');
			$cmpThat = $this->arg('that');

			$strict = $this->arg('strict', false);

			return $strict ? $cmpThis === $cmpThat : $cmpThis == $cmpThat;
		}
	}