<?php
	namespace Adepto\PredicaTree\Predicate\Action;
	
	use Adepto\PredicaTree\Predicate\Action;
	
	abstract class CustomAction extends Action {
		public function __construct(string $method, ...$arguments) {
			parent::__construct([
				'method'	=>	$method,
				'arguments'	=>	$arguments
			]);
		}
		
		public function apply(&$subject) {
			return $subject->{$this->arg('method')}(...$this->arg('arguments'));
		}
		
		protected abstract function getCollectionClass(): string;
		
		protected function getCacheSpecification(): array {
			$refl = new \ReflectionClass(static::getCollectionClass());
			$reflMethod = $refl->getMethod($this->arg('method'));
			
			return [
				'method'	=>	'string!',
				'arguments'	=>	array_filter(array_map(function (\ReflectionParameter $param) {
					if ($param->isOptional()) {
						return null;
					}
					
					return $param->getClass()->name ?? 'any';
				}, $reflMethod->getParameters()))
			];
		}
	}