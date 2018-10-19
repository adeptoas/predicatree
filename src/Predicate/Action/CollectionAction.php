<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Collection\Collection;
	use Adepto\PredicaTree\Predicate\Action;

	class CollectionAction extends Action {
		public function __construct(string $method, ...$arguments) {
			parent::__construct([
				'method'	=>	$method,
				'arguments'	=>	$arguments
			]);
		}

		public function apply(&$subject) {
			if ($subject instanceof Collection) {
				// FIXME prevent injection
				$subject->{$this->arg('method')}(...$this->arg('arguments'));
			}
		}

		public function getPositionalArguments(): array {
			$std = parent::getPositionalArguments();
			return array_values(array_merge([ $std[0] ], $std[1]));
		}

		protected function getCacheSpecification(): array {
			$refl = new \ReflectionClass(Collection::class);
			$reflMethod = $refl->getMethod($this->arg('method'));

			return [
				'method'	=>	'string!',
				'arguments'	=>	array_map(function (\ReflectionParameter $param) {
					if ($param->isOptional()) {
						return null;
					}
					
					return $param->getClass()->name ?? 'any';
				}, $reflMethod->getParameters())
			];
		}
	}