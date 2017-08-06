<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Collection\Collection;
	use Adepto\PredicaTree\Predicate\Action;

	class CollectionAction extends Action {
		public function __construct(...$arguments) {
			parent::__construct($arguments);
		}

		public function apply(&...$subject) {
			$coll = $subject[0];

			if ($coll instanceof Collection) {
				$coll->{$this->getMethod()}(...$this->arg());
			}
		}

		protected function getCacheSpecification(): array {
			return [
				'__root*'	=>	'any'
			];
		}
	}