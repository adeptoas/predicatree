<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Collection\Collection;
	use Adepto\PredicaTree\Predicate\Action;

	class GenericCollectionAction extends Action {
		protected $methodName;

		public function __construct(string $methodName, array $arguments = []) {
			parent::__construct($arguments);

			$this->methodName = $methodName;
		}

		public function apply(Collection &$collection) {
			$collection->{$this->methodName}(...$this->arguments);
		}

		function jsonSerialize() {
			return array_filter([
				'method'	=>	$this->methodName,
				'arguments'	=>	$this->arguments
			]);
		}
	}