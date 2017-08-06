<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;

	abstract class Condition extends CacheObject {
		const BASE_SPECIFICATION = [
			'condition'	=>	'string!',
			'arguments'	=>	'array::seq'
		];

		public static function fromSpecifiedArray(array $data): Condition {
			$operator = strtolower($data['condition']);
			$class = __NAMESPACE__ . '\\Condition\\' . ucfirst(FancyString::toCamelCase($operator)) . 'Condition';

			$args = $data['arguments'];

			return new $class(...$args); // TODO use proper mapping instead of hacking class names
		}

		public abstract function evaluate(): bool;
	}