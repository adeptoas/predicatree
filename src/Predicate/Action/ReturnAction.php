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

		public function apply(&$subject) {
			if (is_array($subject) && !$subject[DecisionProgram::KEY_HAS_RETURNED]) {
				$subject[DecisionProgram::KEY_HAS_RETURNED] = true;
				$subject[DecisionProgram::KEY_RETURN_RESULT] = $this->arg('return');
			}
		}

		protected function getCacheSpecification(): array {
			return [
				'return'	=>	'any'
			];
		}
	}