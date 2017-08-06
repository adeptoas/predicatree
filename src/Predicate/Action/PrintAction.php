<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Predicate\Action;

	class PrintAction extends Action {
		public function __construct($line, bool $json = false) {
			parent::__construct([
				'line'	=>	$line,
				'json'	=>	$json
			]);
		}

		public function apply(&...$subject) {
			$json = $this->arg('json', false);
			$line = $this->arg('line');

			echo ($json ? json_encode($line, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $line) . PHP_EOL;
		}

		protected function getCacheSpecification(): array {
			return [
				'line'	=>	'any',
				'json?'	=>	'bool'
			];
		}
	}