<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;
	use Adepto\SniffArray\Sniff\ArraySniffer;

	abstract class CacheObject implements \JsonSerializable {
		protected $arguments;
		protected $argCache;

		protected function __construct(array $assocArguments) {
			if (ArraySniffer::arrayConformsTo($this->getCacheSpecification(), $assocArguments, true)) {
				$this->arguments = $assocArguments;
				$this->argCache = $assocArguments;
			}
		}

		protected abstract function getCacheSpecification(): array;

		protected function arg(string $key = null, $default = null) {
			if (is_null($key)) {
				return $this->argCache;
			}

			return $this->argCache[$key] ?? $default; // TODO what if the cache completely fails (ie EqualCond null == null)
		}

		public function getDynamicArguments(): array {
			return $this->arguments;
		}

		public function writeArgumentCache(array $dynData) {
			$this->argCache = array_merge($this->arguments, $dynData);
		}

		public function jsonSerialize() {
			$baseName = str_replace(__NAMESPACE__ . '\\', '', get_class($this));

			$snake = FancyString::toSnakeCase($baseName);
			$snakeParts = explode('_', $snake);

			$tail = array_pop($snakeParts);

			return [
				$tail		=>	implode('-', array_map('strtoupper', $snakeParts)),
				'arguments'	=>	$this->getDynamicArguments()
			];
		}
	}