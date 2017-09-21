<?php

	namespace Adepto\PredicaTree\Serialization;

	use Adepto\PredicaTree\Predicate\Predicate;
	use Adepto\PredicaTree\Program\Program;

	class ArgumentHandler {
		protected $applyIf;
		protected $applyThen;
		protected $applyElse;

		protected $mapping;

		public function __construct(bool $applyIf, bool $applyThen, bool $applyElse, callable $mapping) {
			$this->applyIf = $applyIf;
			$this->applyThen = $applyThen;
			$this->applyElse = $applyElse;

			$this->mapping = $mapping;
		}

		public function apply(Program $program) {
			/** @var $predicate Predicate */
			foreach ($program->getPredicates() as $predicate) {
				$predicate->writeFullDynamicCache(array_map(function (array $dynArgs, bool $map) {
					return $map ? $this->handleArgs($dynArgs) : $dynArgs;
				}, $predicate->getFullDynamicArguments(), [
					$this->applyIf,
					$this->applyThen,
					$this->applyElse,
				]));
			}
		}

		protected function handleArgs(array $args) {
			return array_map(function ($val) {
				if (is_array($val)) {
					return $this->handleArgs($val);
				}

				return call_user_func($this->mapping, $val);
			}, $args);
		}
	}