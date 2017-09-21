<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\SniffArray\Sniff\ArraySniffer;

	class Predicate implements \JsonSerializable {
		const BASE_SPECIFICATION = [
			'if'	=>	Condition::BASE_SPECIFICATION,
			'then'	=>	Action::BASE_SPECIFICATION,
			'else?'	=>	Action::BASE_SPECIFICATION
		];

		const SWITCH_SPECIFICATION = [
			'switch'	=>	[
				'value'		=>	'any',
				'case'		=>	'array::assoc',
				'default?'	=>	Action::BASE_SPECIFICATION
			]
		];

		const SWITCH_DETECTION_DEFAULT = 3;

		protected static $switchDetection = self::SWITCH_DETECTION_DEFAULT;

		public static function buildList(array $allPredicateData): array {
			return array_map(function (array $predicateSpec) {
				return self::fromSpecifiedArray($predicateSpec);
			}, $allPredicateData);
		}

		public static function fromSpecifiedArray(array $predicateData): Predicate {
			if ($predicateData['switch'] ?? null) {
				$predicateData = self::unpackSwitchStatement($predicateData);
			}

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

		public function jsonSerialize() {
			return array_filter([
				'if'	=>	$this->condition,
				'then'	=>	$this->action,
				'else'	=>	$this->elseAction
			]);
		}

		public static function getSwitchDetectionDepth(): int {
			return self::$switchDetection;
		}

		public static function setSwitchDetectionDepth(int $depth) {
			self::$switchDetection = $depth;
		}

		public static function resetSwitchDetectionDepth() {
			self::setSwitchDetectionDepth(self::SWITCH_DETECTION_DEFAULT);
		}

		public static function unpackSwitchStatement(array $switchStatement): array {
			if (!ArraySniffer::arrayConformsTo(self::SWITCH_SPECIFICATION, $switchStatement)) {
				return $switchStatement;
			}

			$switchData = $switchStatement['switch'];

			$subject = $switchData['value'];
			$cases = $switchData['case'];
			$default = $switchData['default'] ?? null;

			$recData = [];

			foreach ($cases as $case => $action) {
				$recData[] = [
					'if'	=>	$case,
					'then'	=>	$action
				];
			}

			return self::unpackSwitchRecursion($subject, array_reverse($recData), $default);
		}

		protected static function unpackSwitchRecursion($subject, array $recData, array $default = null): array {
			$predicate = [];
			$current = array_pop($recData);

			$predicate['if'] = [
				'condition'	=>	'EQUAL',
				'arguments'	=>	[$subject, $current['if']]
			];

			$predicate['then'] = $current['then'];

			if (count($recData)) {
				$predicate['else'] = [
					'action'	=>	'EVAL',
					'arguments'	=>	[
						self::unpackSwitchRecursion($subject, $recData, $default)
					]
				];
			} else if ($default) {
				$predicate['else'] = $default;
			}

			return $predicate;
		}

		protected static function buildSwitchDescentConfig(string $subject): array {
			return [
				'action'		=>	'string::^EVAL$',
				'arguments{1}'	=>	[
					'if'	=>	[
						'condition'		=>	'string::^EQUAL$',
						'arguments'		=>	[
							'string::^' . $subject . '$',
							'any'
						]
					]
				]
			];
		}

		public static function packSwitchStatement(array $stdPredicate): array {
			if (!self::checkSwitchEligible($stdPredicate)) {
				return $stdPredicate;
			}

			$topLevelValue = $stdPredicate['if']['arguments'][0];
			$bottomLevelAction = $stdPredicate['else'];

			while (
				is_array($bottomLevelAction)
					&& ArraySniffer::arrayConformsTo(self::buildSwitchDescentConfig($topLevelValue), $bottomLevelAction)
			) {
				$bottomLevelAction = $bottomLevelAction['arguments'][0]['else'] ?? null;
			}

			return [
				'switch'	=>	[
					'value'		=>	$topLevelValue,
					'case'		=>	self::packSwitchRecursion($stdPredicate),
					'default'	=>	$bottomLevelAction
				]
			];
		}

		protected static function packSwitchRecursion(array $stdPredicate): array {
			$subject = $stdPredicate['if']['arguments'][0];
			$object = (string) $stdPredicate['if']['arguments'][1];

			$case = [
				$object	=>	$stdPredicate['then']
			];

			$else = $stdPredicate['else'];

			if (is_array($else) && ArraySniffer::arrayConformsTo(self::buildSwitchDescentConfig($subject), $else)) {
				return $case + self::packSwitchRecursion($else['arguments'][0]);
			}

			return $case;
		}

		public static function checkSwitchEligible(array $predicate): bool {
			return self::checkSwitchDepth($predicate) >= self::getSwitchDetectionDepth();
		}

		protected static function checkSwitchDepth(array $predicate): int {
			$conforms = ArraySniffer::arrayConformsTo([
				'if'	=>	[
					'condition'		=>	'string::^EQUAL$',
					'arguments{2}'	=>	'any'
				],
				'then'	=>	Action::BASE_SPECIFICATION,
				'else?'	=>	'array::assoc'
			], $predicate);

			if ($conforms) {
				if (is_array($predicate['else'] ?? null)) {
					$elseBranch = $predicate['else'];
					$subject = $predicate['if']['arguments'][0];

					if (ArraySniffer::arrayConformsTo(self::buildSwitchDescentConfig($subject), $elseBranch)) {
						return self::checkSwitchDepth($elseBranch['arguments'][0]) + 1;
					}
				}

				return 1;
			}

			return 0;
		}
	}