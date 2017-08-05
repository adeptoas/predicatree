<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;
	use Adepto\SniffArray\Sniff\ArraySniffer;

	abstract class Condition implements \JsonSerializable {
		const BASE_SPECIFICATION = [
			'operator'	=>	'string',
			'operands'	=>	'array::seq'
		];

		public static function fromSpecifiedArray(array $data): Condition {
			$operator = strtolower($data['operator']);
			$class = __NAMESPACE__ . '\\Condition\\' . ucfirst(FancyString::toCamelCase($operator)) . 'Condition';

			$args = $data['operands'];

			return new $class(...$args); // TODO use proper mapping instead of hacking class names
		}

		protected $operands;
		protected $opCache;

		protected function __construct(array $assocOperands) {
			if (ArraySniffer::arrayConformsTo($this->getCacheSpecification(), $assocOperands, true)) {
				$this->operands = $assocOperands;
				$this->opCache = $assocOperands;
			}
		}

		public abstract function evaluate(): bool;

		protected abstract function getCacheSpecification(): array;

		protected function op(string $key, $default = null) {
			return $this->opCache[$key] ?? $default; // TODO what if the cache completely fails (ie EqualCond null == null)
		}

		public function getDynamicOperands(): array {
			return $this->operands;
		}

		public function writeOperandCache(array $dynData) {
			$this->opCache = array_merge($this->operands, $dynData);
		}

		public function jsonSerialize() {
			return [
				'operator'	=>	strtoupper(str_replace('Condition', '', str_replace(__NAMESPACE__ . '\\', '', get_class($this)))),
				'operands'	=>	$this->getDynamicOperands()
			];
		}
	}