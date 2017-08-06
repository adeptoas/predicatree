<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Predicate\Action;
	use Adepto\PredicaTree\Predicate\Predicate;

	class EvalAction extends Action {
		protected $predicate;

		public function __construct(array $predicateData) {
			parent::__construct($predicateData);

			$this->predicate = Predicate::fromSpecifiedArray($predicateData);
		}

		public function apply(&...$subject) {
			$this->predicate->apply(...$subject);
		}

		public function getDynamicArguments(): array {
			return [
				'condition'	=>	$this->predicate->getConditionOperands(),
				'action'	=>	$this->predicate->getActionArguments()
			];
		}

		public function writeArgumentCache(array $dynData) {
			$this->predicate->writeConditionCache($dynData['condition']);
			$this->predicate->writeActionCache($dynData['action']);
		}

		protected function getCacheSpecification(): array {
			return Predicate::BASE_SPECIFICATION;
		}
	}