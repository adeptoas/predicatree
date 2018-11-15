<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;
	use BadFunctionCallException;
	
	abstract class Action extends CacheObject {
		const BASE_SPECIFICATION = [
			'action'	=>	'string!',
			'arguments'	=>	'array::seq',
		];

		public static function fromSpecifiedArray(array $data): Action {
			if (class_exists($data['action'])) {
				//user submitted own action
				$class = $data['action'];
				
				if (!is_subclass_of($class, self::class)) {
					throw new \BadMethodCallException('Action class must extend ' . self::class);
				}
			} else {
				$action = strtolower($data['action']);
				
				if (!class_exists($class = __NAMESPACE__ . '\\Action\\' . ucfirst(FancyString::toCamelCase($action)) . 'Action')) {
					throw new BadFunctionCallException($class . " is not an Action");
				}
			}

			$args = $data['arguments'];

			return new $class(...$args);
		}

		// FIXME pointer or return copy?
		// TODO subject specification checking?
		public abstract function apply(&$subject);
	}