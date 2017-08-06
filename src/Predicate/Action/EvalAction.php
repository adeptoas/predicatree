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
			return $this->predicate->getFullDynamicArguments();
		}

		public function writeArgumentCache(array $dynData) {
			$this->predicate->writeFullDynamicCache($dynData);
		}

		public function getPositionalArguments(): array {
			return [ $this->predicate ];
		}

		protected function getCacheSpecification(): array {
			return Predicate::BASE_SPECIFICATION;
		}
	}