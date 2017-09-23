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
				'value'								=>	'any',
				'case+'								=>	Action::BASE_SPECIFICATION,
				'default?'							=>	Action::BASE_SPECIFICATION,
			],
			self::SWITCH_CASE_DELIMITER . '?'	=>	'string!'
		];

		const SWITCH_DETECTION_DEFAULT = 3;
		const SWITCH_CASE_DELIMITER = 'case-delimiter';

		protected static $switchDepth = self::SWITCH_DETECTION_DEFAULT;
		protected static $switchDetectionEnabled = true;

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
		public function apply(&$subject) {
			if ($this->condition->evaluate()) {
				$this->action->apply($subject);
			} else if ($this->elseAction instanceof Action) {
				$this->elseAction->apply($subject);
			}
		}

		public function jsonSerialize() {
			if (self::isSwitchDetectionEnabled()) {
				self::setSwitchDetectionEnabled(false);
				$jsonArr = json_decode(json_encode($this->jsonSerialize()), true);

				self::setSwitchDetectionEnabled(true);
				$pack = self::packInwardsNestedSwitch($jsonArr);

				return self::collapseInwardsSwitch($pack);
			} else {
				return array_filter([
					'if'	=>	$this->condition,
					'then'	=>	$this->action,
					'else'	=>	$this->elseAction
				]);
			}
		}

		public static function getSwitchDepth(): int {
			return self::$switchDepth;
		}

		public static function setSwitchDepth(int $depth) {
			self::$switchDepth = $depth;
		}

		public static function resetSwitchDepth() {
			self::setSwitchDepth(self::SWITCH_DETECTION_DEFAULT);
		}

		public static function isSwitchDetectionEnabled(): bool {
			return self::$switchDetectionEnabled;
		}

		public static function setSwitchDetectionEnabled(bool $enabled = true) {
			self::$switchDetectionEnabled = $enabled;
		}

		public static function unpackSwitchStatement(array $switchStatement): array {
			if (!ArraySniffer::arrayConformsTo(self::SWITCH_SPECIFICATION, $switchStatement)) {
				return $switchStatement;
			}

			$switchData = $switchStatement['switch'];
			$delim = $switchStatement[self::SWITCH_CASE_DELIMITER] ?? null;

			$subject = $switchData['value'];
			$cases = $switchData['case'];
			$default = $switchData['default'] ?? null;

			$recData = [];

			foreach ($cases as $case => $action) {
				if ($delim && strpos($case, $delim) !== false) {
					foreach (explode($delim, $case) as $subCase) {
						$recData[] = [
							'if'	=>	$subCase,
							'then'	=>	$action
						];
					}
				} else {
					$recData[] = [
						'if'	=>	$case,
						'then'	=>	$action
					];
				}
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

		protected static function packInwardsNestedSwitch(array $predicate): array {
			return array_map(function ($val) {
				if (is_array($val)) {
					if (ArraySniffer::arrayConformsTo(Action::BASE_SPECIFICATION, $val)) {
						$val = self::packInwardsNestedAction($val, function (array $nested) {
							return self::packInwardsNestedSwitch($nested);
						});
					} else if (ArraySniffer::arrayConformsTo(self::SWITCH_SPECIFICATION['switch'], $val)) {
						$val['case'] = array_map(function ($arg) {
							return self::packInwardsNestedAction($arg, function (array $nested) {
								return self::packInwardsNestedSwitch($nested);
							});
						}, $val['case']);
					}
				}

				return $val;
			}, self::packSwitchStatement($predicate));
		}

		protected static function packInwardsNestedAction(array $action, callable $evalFunction): array {
			if ($action['action'] == 'EVAL') {
				$action['arguments'] = array_map(function ($arg) use ($evalFunction) {
					return call_user_func($evalFunction, $arg);
				}, $action['arguments']);
			} else if ($action['action'] == 'CHAIN') {
				$action['arguments'] = array_map(function ($arg) use ($evalFunction) {
					return self::packInwardsNestedAction($arg, $evalFunction);
				}, $action['arguments']);
			}

			return $action;
		}

		protected static function collapseInwardsSwitch(array $predicate): array {
			$collapsed = [];

			foreach ($predicate as $key => $item) {
				$delim = null;

				if (in_array($key, [ 'then', 'else' ])) {
					$item = self::packInwardsNestedAction($item, function (array $nested) {
						return self::collapseInwardsSwitch($nested);
					});
				} else if ($key == 'switch') {
					$multiCases = [];
					$handled = [];

					foreach ($item['case'] as $caseLabel => $action) {
						$caseRegistry = [ $caseLabel ];

						foreach ($item['case'] as $altCaseLabel => $altAction) {
							if ($altCaseLabel == $caseLabel || in_array($altCaseLabel, $handled)) {
								continue;
							}

							if ($altAction == $action) {
								$caseRegistry[] = $altCaseLabel;
								$handled[] = $altCaseLabel;
							}
						}

						if (!in_array($caseLabel, $handled)) {
							$multiCases[] = $caseRegistry;
							$handled[] = $caseLabel;
						}
					}

					if (count($item['case']) != count($multiCases)) {
						$caseBook = [];

						$delimChoice = '%/&#|*_:';
						$delimCount = 2;
						$delimIndex = 0;

						$delim = str_repeat($delimChoice[$delimIndex], $delimCount);

						while (array_reduce($multiCases, function (bool $foundCarry, array $cache) use ($delim) {
							return $foundCarry || array_reduce($cache, function (bool $strPosCarry, string $caseKey) use ($delim) {
								return $strPosCarry || strpos($caseKey, $delim) !== false;
							}, false);
						}, false)) {
							$delimIndex++;

							if ($delimIndex == strlen($delimChoice)) {
								$delimIndex = 0;
								$delimCount++;
							}

							$delim = str_repeat($delimChoice[$delimIndex], $delimCount);
						}

						foreach ($multiCases as $caseTuple) {
							$randomCase = $caseTuple[array_rand($caseTuple)];
							$action = $item['case'][$randomCase];

							$caseBook[implode($delim, $caseTuple)] = $action;
						}
					} else {
						$caseBook = $item['case'];
					}

					$item['case'] = array_map(function ($arg) {
						return self::packInwardsNestedAction($arg, function (array $nested) {
							return self::collapseInwardsSwitch($nested);
						});
					}, $caseBook);
				}

				$collapsed[$key] = $item;
				$collapsed[self::SWITCH_CASE_DELIMITER] = $delim;
			}

			return array_filter($collapsed);
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
				'switch'	=>	array_filter([
					'value'		=>	$topLevelValue,
					'case'		=>	self::packSwitchRecursion($stdPredicate),
					'default'	=>	$bottomLevelAction
				])
			];
		}

		protected static function packSwitchRecursion(array $stdPredicate): array {
			$subject = $stdPredicate['if']['arguments'][0];
			$object = (string) $stdPredicate['if']['arguments'][1];

			$case = [
				$object	=>	$stdPredicate['then']
			];

			$else = $stdPredicate['else'] ?? null;

			if (is_array($else) && ArraySniffer::arrayConformsTo(self::buildSwitchDescentConfig($subject), $else)) {
				return $case + self::packSwitchRecursion($else['arguments'][0]);
			}

			return $case;
		}

		public static function checkSwitchEligible(array $predicate): bool {
			return self::checkSwitchDepth($predicate) >= self::getSwitchDepth();
		}

		protected static function checkSwitchDepth(array $predicate): int {
			$conforms = ArraySniffer::arrayConformsTo([
				'if'	=>	[
					'condition'			=>	'string::^EQUAL$',
					'arguments{2,3}'	=>	'any'
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