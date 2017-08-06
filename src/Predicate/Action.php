<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;

	abstract class Action extends CacheObject {
		const METHOD_DEFAULT = '__std';

		const BASE_SPECIFICATION = [
			'action'	=>	'string!',
			'arguments'	=>	'array::seq',
			'method?'	=>	'string!'
		];

		public static function fromSpecifiedArray(array $data): Action {
			$action = strtolower($data['action']);
			$class = __NAMESPACE__ . '\\Action\\' . ucfirst(FancyString::toCamelCase($action)) . 'Action';

			$args = $data['arguments'];

			/** @var $actionObj Action */
			$actionObj = new $class(...$args);

			if ($method = $data['method']) {
				$actionObj->setMethod($method);
			}

			return $actionObj;
		}

		protected $method;

		public function __construct(array $assocArguments) {
			parent::__construct($assocArguments);

			$this->method = static::METHOD_DEFAULT;
		}

		public function setMethod(string $method) {
			$this->method = $method;
		}

		public function getMethod(): string {
			return $this->method;
		}

		// FIXME pointer or return copy?
		public abstract function apply(&...$subject);

		public function jsonSerialize() {
			return array_merge(array_filter([
				'method'	=>	$this->getMethod()
			], function (string $method) {
				return $method !== self::METHOD_DEFAULT;
			}), parent::jsonSerialize());
		}
	}