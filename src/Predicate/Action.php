<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;

	abstract class Action extends CacheObject {
		const BASE_SPECIFICATION = [
			'action'	=>	'string!',
			'arguments'	=>	'array::seq',
		];

		public static function fromSpecifiedArray(array $data): Action {
			if (class_exists($data['action'])) {
				//user submitted own action
				$class = $data['action'];
				$reflection = new \ReflectionClass($class);
				
				if ($reflection->isSubclassOf(self::class)) {
					throw new \BadMethodCallException('Action class must extend ' . self::class);
				}
			} else {
				$action = strtolower($data['action']);
				$class = __NAMESPACE__ . '\\Action\\' . ucfirst(FancyString::toCamelCase($action)) . 'Action';
			}
			
			$args = $data['arguments'];

			return new $class(...$args);
		}

		// FIXME pointer or return copy?
		// TODO subject specification checking?
		public abstract function apply(&$subject);
	}