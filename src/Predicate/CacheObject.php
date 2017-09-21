<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\Fancy\FancyString;
	use Adepto\SniffArray\Sniff\ArraySniffer;

	abstract class CacheObject implements \JsonSerializable {
		protected $arguments;
		protected $argCache;

		protected function __construct(array $assocArguments) {
			$this->arguments = $assocArguments;
			$this->argCache = $assocArguments;

			# check that everything is okay after fields have been initialized
			# because some specification builders rely on the "assocArguments" already
			$sniffer = new ArraySniffer($this->getCacheSpecification(), true); // FIXME EvalAction can have multiple specifications (switch…)
			$sniffer->sniff($assocArguments);
		}

		protected abstract function getCacheSpecification(): array;

		protected function arg(string $key, $default = null) {
			return $this->argCache[$key] ?? $default; // TODO what if the cache completely fails (ie EqualCond null == null)
		}

		public function getDynamicArguments(): array {
			return $this->arguments;
		}

		public function getPositionalArguments(): array {
			return array_values($this->getDynamicArguments());
		}

		public function writeArgumentCache(array $dynData) {
			$this->argCache = array_merge($this->arguments, $dynData);
		}

		public function jsonSerialize() {
			$baseName = str_replace(__NAMESPACE__ . '\\', '', get_class($this));
			$baseNameParts = explode('\\', $baseName);
			$baseName = array_pop($baseNameParts);

			$snake = FancyString::toSnakeCase($baseName);
			$snakeParts = explode('_', $snake);

			$tail = strtolower(array_pop($snakeParts));

			return [
				$tail		=>	implode('-', array_map('strtoupper', $snakeParts)),
				'arguments'	=>	$this->getPositionalArguments()
			];
		}
	}