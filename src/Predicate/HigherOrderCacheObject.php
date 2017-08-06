<?php
	namespace Adepto\PredicaTree\Predicate;

	trait HigherOrderCacheObject {
		protected $nestedCaches;

		public function __construct(array ...$caches) {
			parent::__construct($caches);

			$this->nestedCaches = array_map([static::class, 'fromSpecifiedArray'], $caches);
		}

		public function getDynamicArguments(): array {
			return array_map(function (Condition $cond) {
				return $cond->getDynamicArguments();
			}, $this->nestedCaches);
		}

		public function writeArgumentCache(array $dynData) {
			array_map(function (Condition $cond, array $cache) {
				$cond->writeArgumentCache($cache);
			}, $this->nestedCaches, $dynData);
		}

		protected function getChild(int $i): CacheObject {
			return $this->nestedCaches[$i];
		}

		protected function getChildren(): array {
			return $this->nestedCaches;
		}

		protected function getChildCount(): int {
			return count($this->getChildren());
		}

		protected function getCacheSpecification(): array {
			return [
				'__root' . $this->getArityModifier()	=>	static::BASE_SPECIFICATION
			];
		}

		protected function getArityModifier(): string {
			return '+';
		}
	}