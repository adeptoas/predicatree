<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\PredicaTree\Collection\Collection;

	class Predicate implements \JsonSerializable {
		public static function buildList(array $allPredicateData): array {
			return array_map(function (array $predicateSpec) {
				return self::fromSpecifiedArray($predicateSpec);
			}, $allPredicateData);
		}

		public static function fromSpecifiedArray(array $predicateData): Predicate {
			$condition = Condition::fromSpecifiedArray($predicateData['if']);
			$action = Action::fromSpecifiedArray($predicateData['then']);

			return new self($condition, $action); // TODO use cache for equal objects?
		}

		protected $condition;
		protected $action;

		public function __construct(Condition $condition, Action $action) {
			$this->condition = $condition;
			$this->action = $action;
		}

		public function getConditionOperands(): array {
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

		// FIXME use pointer here or just return another object copy?
		public function apply(Collection &$collection) {
			if ($this->condition->evaluate()) {
				$this->action->apply($collection);
			}
		}

		function jsonSerialize() {
			return [
				'if'	=>	$this->condition,
				'then'	=>	$this->action
			];
		}
	}