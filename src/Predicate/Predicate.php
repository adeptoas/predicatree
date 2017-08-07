<?php
	namespace Adepto\PredicaTree\Predicate;

	class Predicate implements \JsonSerializable {
		const BASE_SPECIFICATION = [
			'if'	=>	Condition::BASE_SPECIFICATION,
			'then'	=>	Action::BASE_SPECIFICATION,
			'else?'	=>	Action::BASE_SPECIFICATION
		];

		public static function buildList(array $allPredicateData): array {
			return array_map(function (array $predicateSpec) {
				return self::fromSpecifiedArray($predicateSpec);
			}, $allPredicateData);
		}

		public static function fromSpecifiedArray(array $predicateData): Predicate {
			$condition = Condition::fromSpecifiedArray($predicateData['if']);
			$action = Action::fromSpecifiedArray($predicateData['then']);

			if ($altAction = $predicateData['else'] ?? null) {
				$elseAction = Action::fromSpecifiedArray($altAction);
			} else {
				$elseAction = null;
			}

			return new self($condition, $action, $elseAction); // TODO use cache for equal objects?
		}

		protected $condition;

		protected $action;
		protected $elseAction;

		public function __construct(Condition $condition, Action $action, Action $elseAction = null) {
			$this->condition = $condition;

			$this->action = $action;
			$this->elseAction = $elseAction;
		}

		public function getConditionArguments(): array {
			return $this->condition->getDynamicArguments();
		}

		public function writeConditionCache(array $dynData) {
			$this->condition->writeArgumentCache($dynData);
		}

		public function getActionArguments(): array {
			return $this->action->getDynamicArguments();
		}

		public function writeActionCache(array $dynData) {
			$this->action->writeArgumentCache($dynData);
		}

		public function getElseActionArguments(): array {
			if (is_null($this->elseAction)) {
				return [];
			}

			return $this->elseAction->getDynamicArguments();
		}

		public function writeElseActionCache(array $dynData) {
			if (!is_null($this->elseAction)) {
				$this->elseAction->writeArgumentCache($dynData);
			}
		}

		public function getFullDynamicArguments(): array {
			return [
				$this->getConditionArguments(),
				$this->getActionArguments(),
				$this->getElseActionArguments()
			];
		}

		public function writeFullDynamicCache(array $dynData) {
			list($condCache, $actionCache, $elseActionCache) = $dynData;

			$this->writeConditionCache($condCache);
			$this->writeActionCache($actionCache);
			$this->writeElseActionCache($elseActionCache);
		}

		// FIXME use pointer here or just return another object copy?
		public function apply(&...$subject) {
			if ($this->condition->evaluate()) {
				$this->action->apply(...$subject);
			} else if ($this->elseAction instanceof Action) {
				$this->elseAction->apply(...$subject);
			}
		}

		function jsonSerialize() {
			return array_filter([
				'if'	=>	$this->condition,
				'then'	=>	$this->action,
				'else'	=>	$this->elseAction
			]);
		}
	}