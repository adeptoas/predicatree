<?php
	namespace Adepto\PredicaTree\Predicate;

	abstract class Condition implements \JsonSerializable {
		public static function fromSpecifiedArray(array $data): Condition {
			$operator = $data['operator'];
			$class = __NAMESPACE__ . '\\' . ucfirst(strtolower($operator)) . 'Condition';

			$args = $data['operands'];

			return new $class(...$args); // FIXME use proper mapping instead of hacking class names
		}

		public abstract function evaluate(array $dynamicData = []): bool;

		public abstract function getDynamicIdentifiers(): array;
	}