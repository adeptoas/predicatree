<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;

	abstract class Action extends CacheObject {
		const BASE_SPECIFICATION = [
			'action'	=>	'string!',
			'arguments'	=>	'array::seq',
		];

		public static function fromSpecifiedArray(array $data): Action {
			$action = strtolower($data['action']);
			$class = __NAMESPACE__ . '\\Action\\' . ucfirst(FancyString::toCamelCase($action)) . 'Action';

			$args = $data['arguments'];

			return new $class(...$args);
		}

		// FIXME pointer or return copy?
		public abstract function apply(&...$subject);
	}