<?php
	namespace Adepto\PredicaTree\Predicate;

	use Adepto\PredicaTree\Collection\Collection;

	abstract class Action implements \JsonSerializable {
		public static function fromSpecifiedArray(array $data): Action {
			$method = $data['method'];
			$args = $data['arguments'];

			return new GenericCollectionAction($method, $args); // FIXME this is only preliminary
		}

		protected $arguments;

		public function __construct(array $arguments = []) {
			$this->arguments = $arguments;
		}

		// FIXME same question as above: pointer or return copy?
		public abstract function apply(Collection &$collection);
	}