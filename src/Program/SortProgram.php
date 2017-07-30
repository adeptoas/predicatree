<?php
	namespace Adepto\PredicaTree\Program;

	use Adepto\Fancy\FancyArray;
	use Adepto\PredicaTree\Collection\Collection;
	use Adepto\PredicaTree\Predicate\Predicate;

	class SortProgram implements \JsonSerializable {
		public static function build(array $data): SortProgram {
			$apriori = $data['apriori'];

			$collection = Collection::fromSpecifiedArray($data['collection']);
			$predicates = Predicate::buildList($data['predicates']);

			return new self(
				$collection,
				$predicates,
				$apriori
			);
		}

		protected $collection;
		protected $predicates;

		protected $aprioriData;

		public function __construct(Collection $collection, array $predicates, array $aprioriData = []) {
			$this->collection = $collection;
			$this->predicates = $predicates;

			$this->aprioriData = $aprioriData;
		}

		public function getPredicates(): array {
			return $this->predicates;
		}

		public function addAprioriData(string $key, $value) {
			$this->aprioriData[$key] = $value;
		}

		public function getAprioriData(string $key = null) {
			if (is_null($key)) {
				return $this->aprioriData;
			}

			return FancyArray::colonAccess($this->aprioriData, $key, Predicate::DYN_MARKER_APRIORI);
		}

		public function getCollectionData($key = null) {
			if (is_null($key)) {
				return $this->collection;
			}

			return $this->collection->get($key);
		}

		public function getDynamicData(string $key) {
			$prefix = substr($key, 0, 2);
			$dataKey = substr($key, 2);

			if ($prefix == Predicate::DYN_MARKER_APRIORI) {
				return $this->getAprioriData($dataKey);
			} else if ($prefix == Predicate::DYN_MARKER_COLLECTION) {
				return $this->getCollectionData($dataKey);
			}

			return null;
		}

		public function sort(): Collection {
			$applCollection = clone $this->collection;

			/** @var $predicate Predicate */
			foreach ($this->predicates as $predicate) {
				$identifiers = $predicate->getDynamicIdentifiers();
				$dynamic = array_map([$this, 'getDynamicData'], $identifiers);

				$predicate->apply($applCollection, $dynamic);
			}

			return $applCollection;
		}

		function jsonSerialize() {
			return [
				'apriori'		=>	$this->getAprioriData(),
				'collection'	=>	$this->getCollectionData(),
				'predicates'	=>	$this->getPredicates()
			];
		}
	}