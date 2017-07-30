<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\PredicaTree\Collection\Collection;

	class Predicate implements \JsonSerializable {
		const DYN_MARKER_APRIORI = '::';
		const DYN_MARKER_COLLECTION = '__';

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

		public function getDynamicIdentifiers() {
			return array_filter($this->condition->getDynamicIdentifiers(), function ($val) {
				if (!is_string($val)) {
					return false;
				}

				return strpos($val, self::DYN_MARKER_APRIORI) === 0
					|| strpos($val, self::DYN_MARKER_COLLECTION) === 0;
			});
		}

		// FIXME use pointer here or just return another collection?
		public function apply(Collection &$collection, $subject) {
			if ($this->condition->evaluate($subject)) {
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