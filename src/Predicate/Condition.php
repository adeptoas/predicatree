<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;
	use BadFunctionCallException;
	
	abstract class Condition extends CacheObject {
		const BASE_SPECIFICATION = [
			'condition'	=>	'string!',
			'arguments'	=>	'array::seq'
		];

		public static function fromSpecifiedArray(array $data): Condition {
			$operator = strtolower($data['condition']);
			if (!class_exists($class = __NAMESPACE__ . '\\Condition\\' . ucfirst(FancyString::toCamelCase($operator)) . 'Condition')) {
				throw new BadFunctionCallException($class . ' is not a condition');
			}

			$args = $data['arguments'];

			return new $class(...$args); // TODO use proper mapping instead of hacking class names
		}

		public abstract function evaluate(): bool;
	}