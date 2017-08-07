<?php
	namespace Adepto\PredicaTree\Serialization;

	use Adepto\PredicaTree\Predicate\CacheObject;
	use Adepto\PredicaTree\Predicate\Predicate;
	use Adepto\PredicaTree\Program\Program;

	class LinearSerializer implements Serializer {
		const LINEAR_LINK_MODIFIER = '%%LIN';
		const LINEAR_REGISTRY_INDEX = '%%REG';
		const LINEAR_HASH_INDEX = '%%HASH';
		const LINEAR_PREDICATE_MARKER = '%%PRED';
		const LINEAR_NEST_MARKER = '%%NEST';

		protected $deserializationCache;

		public function __construct() {
			$this->deserializationCache = [];
		}

		protected function resetDeserializationCache() {
			$this->deserializationCache = [];
		}

		protected function cacheDeserializable(int $num) {
			$this->deserializationCache[] = $num;
		}

		protected function getDeserializationCaches() {
			return array_unique($this->deserializationCache);
		}

		public function serialize(Program $program): array {
			$flat = $this->flatten($program->getPredicates());
			$flat = $this->sanitizeAndLink($flat);
			$flat = $this->discardUnusedLinks($flat);

			return $flat;
		}

		protected function flatten(array $arr): array {
			$flat = [];

			foreach ($arr as $val) {
				if ($val instanceof Predicate) {
					$serialPredicate = $val->jsonSerialize();

					$actualFlatPredicate = [];
					$nestedPredicates = [];

					$flatPredicate = array_map(function ($val) {
						return $this->flatten([ $val ]);
					}, $serialPredicate);

					foreach ($flatPredicate as $stat => $branch) {
						foreach ($branch as $item) {
							$pred = $item[self::LINEAR_PREDICATE_MARKER] ?? false;
							$reg = $item[self::LINEAR_REGISTRY_INDEX] ?? false;

							if ($pred && $reg) {
								$nestedPredicates[] = $item;
							} else {
								$actualFlatPredicate[$stat][] = $item;
							}
						}
					}

					$actualFlatPredicate[self::LINEAR_HASH_INDEX] = md5(json_encode($serialPredicate));
					$actualFlatPredicate[self::LINEAR_PREDICATE_MARKER] = true;

					$flat[] = $actualFlatPredicate;
					$flat = array_merge($flat, $nestedPredicates);
				} else if ($val instanceof CacheObject) {
					$posArgs = $val->getPositionalArguments();
					$flatInner = $this->flatten($posArgs);

					$serial = $val->jsonSerialize();

					if ($flatInner == $posArgs) {
						$serial[self::LINEAR_HASH_INDEX] = md5(json_encode($serial));
						$flat[] = $serial;
					} else {
						$flatArgs = [];
						$usedArgRegistry = [];

						foreach ($flatInner as $item) {
							$used = $item[self::LINEAR_REGISTRY_INDEX] ?? in_array($item, $usedArgRegistry, true);
							$nestedPredicate = $item[self::LINEAR_PREDICATE_MARKER] ?? false;

							if (!$used) {
								$flatArgs[] = self::LINEAR_LINK_MODIFIER . ($nestedPredicate ? self::LINEAR_PREDICATE_MARKER : '') . $item[self::LINEAR_HASH_INDEX];
								$usedArgRegistry[] = $item;
							}
						}

						$serial['arguments'] = $flatArgs;
						$serial[self::LINEAR_HASH_INDEX] = md5(json_encode($serial));

						$flat[] = $serial;

						foreach ($flatInner as $item) {
							$item[self::LINEAR_REGISTRY_INDEX] = $item[self::LINEAR_REGISTRY_INDEX] ?? in_array($item, $usedArgRegistry, true);
							$flat[] = $item;
						}
					}

				} else {
					$flat[] = $val;
				}

			}

			return $flat;
		}

		protected function sanitizeAndLink(array $flatRaw): array {
			return array_map(function (array $predicate) use ($flatRaw) {
				$flatNice = array_map(function ($sequence) use ($flatRaw) {
					if (!is_array($sequence)) {
						return $sequence;
					}

					return array_map(function ($item) use ($flatRaw, $sequence) {
						unset($item[self::LINEAR_REGISTRY_INDEX]); // Remove "registry" anchor
						unset($item[self::LINEAR_PREDICATE_MARKER]); // Remove "predicate" anchor

						$item['arguments'] = array_map(function ($arg) use ($flatRaw, $sequence) {
							if (is_string($arg) && strpos($arg, self::LINEAR_LINK_MODIFIER) === 0) {
								$linkHash = substr($arg, strlen(self::LINEAR_LINK_MODIFIER));
								$modifier = self::LINEAR_LINK_MODIFIER;
								$search = $sequence;

								if (strpos($linkHash, self::LINEAR_PREDICATE_MARKER) === 0) {
									$linkHash = substr($linkHash, strlen(self::LINEAR_PREDICATE_MARKER));
									$modifier .= self::LINEAR_PREDICATE_MARKER;
									$search = $flatRaw;
								}

								for ($i = 0; $i < count($search); $i++) {
									$current = $search[$i];

									if ($current[self::LINEAR_HASH_INDEX] == $linkHash) {
										return $modifier . $i;
									}
								}

								return $modifier . -1;
							}

							return $arg;
						}, $item['arguments']);

						// It's safe to remove the hash here already b/c an item further down can't link to an item further up (=out) in the structure
						unset($item[self::LINEAR_HASH_INDEX]);

						return $item;
					}, $sequence);
				}, $predicate);

				unset($flatNice[self::LINEAR_REGISTRY_INDEX]);
				unset($flatNice[self::LINEAR_PREDICATE_MARKER]);
				unset($flatNice[self::LINEAR_HASH_INDEX]);

				return $flatNice;
			}, $flatRaw);
		}

		protected function discardUnusedLinks(array $linearFlatArr): array {
			return array_map(function (array $predicate) {
				return array_map(function ($sequence) {
					return array_values(array_intersect_key($sequence, array_flip(array_values(array_merge([0], ...array_map(function ($item) {
						return array_map(function (string $linkIndex) {
							return substr($linkIndex, strlen(self::LINEAR_LINK_MODIFIER));
						}, array_filter($item['arguments'], function ($arg) {
							return is_string($arg)
								&& strpos($arg, self::LINEAR_LINK_MODIFIER) === 0
								&& strpos(substr($arg, strlen(self::LINEAR_LINK_MODIFIER)), self::LINEAR_PREDICATE_MARKER) !== 0;
						}));
					}, $sequence))))));
				}, $predicate);
			}, $linearFlatArr);
		}

		public function deserialize(array $rules, callable $builder): Program {
			$this->resetDeserializationCache();

			$predicates = array_values(array_filter(array_map(function (array $predicate) use ($rules) {
				return array_map(function (array $sequence) use ($rules) {
					return $this->unpackLinkedSequence($sequence[0], $sequence, $rules);
				}, $predicate);
			}, $rules), function ($key) {
				return !in_array($key, $this->getDeserializationCaches());
			}, ARRAY_FILTER_USE_KEY));

			return call_user_func($builder, Predicate::buildList($predicates));
		}

		protected function unpackLinkedSequence(array $node, array $linkedSequence, array $predicateSequence): array {
			$node['arguments'] = array_map(function ($arg) use ($linkedSequence, $predicateSequence) {
				if (is_string($arg) && strpos($arg, self::LINEAR_LINK_MODIFIER) === 0) {
					$linkNum = substr($arg, strlen(self::LINEAR_LINK_MODIFIER));

					if (strpos($linkNum, self::LINEAR_PREDICATE_MARKER) === 0) {
						$linkNum = substr($linkNum, strlen(self::LINEAR_PREDICATE_MARKER));
						$subNode = $predicateSequence[$linkNum];

						$this->cacheDeserializable($linkNum);

						return array_map(function (array $subSequence) use ($predicateSequence) {
							return $this->unpackLinkedSequence($subSequence[0], $subSequence, $predicateSequence);
						}, $subNode);
					}

					$subNode = $linkedSequence[$linkNum];
					return $this->unpackLinkedSequence($subNode, $linkedSequence, $predicateSequence);
				}

				return $arg;
			}, $node['arguments']);

			return $node;
		}
	}