<?php
	namespace Adepto\PredicaTree\Serialization;

	use Adepto\PredicaTree\Program\Program;

	interface Serializer {
		public function serialize(Program $program): array;

		public function deserialize(array $rules, callable $builder): Program;
	}