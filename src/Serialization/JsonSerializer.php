<?php
	namespace Adepto\PredicaTree\Serialization;

	use Adepto\PredicaTree\Predicate\Predicate;
	use Adepto\PredicaTree\Program\Program;

	class JsonSerializer implements Serializer {
		public function serialize(Program $program): array {
			return array_map('json_encode', $program->getPredicates());
		}

		public function deserialize(array $rules, callable $builder): Program {
			return call_user_func($builder, array_map(function (string $jsonRule) {
				return Predicate::fromSpecifiedArray(json_decode($jsonRule, true));
			}, $rules));
		}
	}