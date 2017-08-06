<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Predicate\Action;

	class PrintAction extends Action {
		public function __construct(string $line) {
			parent::__construct([
				'line'	=>	$line
			]);
		}

		public function apply(&...$subject) {
			echo $this->arg('line') . PHP_EOL;
		}

		protected function getCacheSpecification(): array {
			return [
				'line'	=>	'string'
			];
		}
	}