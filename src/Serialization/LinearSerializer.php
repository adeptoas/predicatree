<?php
	namespace Adepto\PredicaTree\Serialization;

	use Adepto\PredicaTree\Predicate\CacheObject;
	use Adepto\PredicaTree\Predicate\Predicate;
	use Adepto\PredicaTree\Program\Program;

	class LinearSerializer implements Serializer {
		const LINEAR_LINK_MODIFIER = '%%LIN%%';

		public function serialize(Program $program): array {
			$flat = $this->flatten($program->getPredicates());
			$flat = $this->sanitizeAndLink($flat);

			return $flat;
		}

		protected function flatten(array $arr): array {
			$flat = [];

			foreach ($arr as $val) {
				if ($val instanceof Predicate) {
					$flat[] = array_map(function ($val) {
						return $this->flatten([ $val ]);
					}, $val->jsonSerialize());
				} else if ($val instanceof CacheObject) {
					$flatInner = $this->flatten($val->getPositionalArguments());
					$serial = $val->jsonSerialize();

					if ($flatInner == $val->getPositionalArguments()) {
						$serial['hash'] = md5(json_encode($serial));
						$flat[] = $serial;
					} else {
						$flatArgs = [];
						$usedArgRegistry = [];

						foreach ($flatInner as $item) {
							$used = $item['used'] ?? in_array($item, $usedArgRegistry, true);

							if (!$used) {
								$flatArgs[] = self::LINEAR_LINK_MODIFIER . $item['hash'];
								$usedArgRegistry[] = $item;
							}
						}

						$serial['arguments'] = $flatArgs;
						$serial['hash'] = md5(json_encode($serial));

						$flat[] = $serial;

						foreach ($flatInner as $item) {
							$item['used'] = in_array($item, $usedArgRegistry, true);
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
			return array_map(function (array $predicate) {
				return array_map(function (array $sequence) {
					return array_map(function ($item) use ($sequence) {
						unset($item['used']); // Remove "registry" anchor

						$item['arguments'] = array_map(function ($arg) use ($sequence) {
							if (is_string($arg) && strpos($arg, self::LINEAR_LINK_MODIFIER) === 0) {
								$linkHash = substr($arg, strlen(self::LINEAR_LINK_MODIFIER));

								for ($i = 0; $i < count($sequence); $i++) {
									$search = $sequence[$i];

									if ($search['hash'] == $linkHash) {
										return self::LINEAR_LINK_MODIFIER . $i;
									}
								}

								return self::LINEAR_LINK_MODIFIER . -1;
							}

							return $arg;
						}, $item['arguments']);

						unset($item['hash']);
						return $item;
					}, $sequence);
				}, $predicate);
			}, $flatRaw);
		}

		public function deserialize(array $rules, callable $builder): Program {
			$predicates = array_map(function (array $predicate) {
				return array_map(function (array $sequence) {
					$rootNode = $sequence[0];
					return $this->unpackLinkedSequence($rootNode, $sequence);
				}, $predicate);
			}, $rules);

			return call_user_func($builder, Predicate::buildList($predicates));
		}

		protected function unpackLinkedSequence(array $node, array $linkedSequence): array {
			$node['arguments'] = array_map(function ($arg) use ($linkedSequence) {
				if (is_string($arg) && strpos($arg, self::LINEAR_LINK_MODIFIER) === 0) {
					$linkNum = substr($arg, strlen(self::LINEAR_LINK_MODIFIER));
					$subNode = $linkedSequence[$linkNum];

					return $this->unpackLinkedSequence($subNode, $linkedSequence);
				}

				return $arg;
			}, $node['arguments']);

			return $node;
		}
	}