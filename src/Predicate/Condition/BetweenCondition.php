<?php
	namespace Adepto\PredicaTree\Predicate\Condition;

	use Adepto\PredicaTree\Predicate\Condition;

	class BetweenCondition extends Condition {
		public function __construct($val, $lower, $upper) {
			parent::__construct([
				'val'	=>	$val,
				'lower'	=>	$lower,
				'upper'	=>	$upper
			]);
		}

		public function evaluate(): bool {
			$val = $this->op('val');

			$lower = $this->op('lower');
			$upper = $this->op('upper');

			$lowerBound = $lower <=> $val;
			$upperBound = $upper <=> $val;

			return $lowerBound <= 0 && $upperBound >= 0;
		}

		protected function getCacheSpecification(): array {
			return [
				'val'	=>	'any!',
				'lower'	=>	'any!',
				'upper'	=>	'any!'
			];
		}
	}