<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Predicate\Action;
	use Adepto\PredicaTree\Program\DecisionProgram;

	class ReturnAction extends Action {
		protected function __construct($returnVal) {
			parent::__construct([
				'return'	=>	$returnVal
			]);
		}

		public function apply(&...$subject) {
			$arr = &$subject[0];

			if (is_array($arr) && !$arr[DecisionProgram::KEY_HAS_RETURNED]) {
				$arr[DecisionProgram::KEY_HAS_RETURNED] = true;
				$arr[DecisionProgram::KEY_RETURN_RESULT] = $this->arg('return');
			}
		}

		protected function getCacheSpecification(): array {
			return [
				'return'	=>	'any'
			];
		}
	}