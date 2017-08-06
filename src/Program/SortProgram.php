<?php
	namespace Adepto\PredicaTree\Program;

	use Adepto\Fancy\FancyArray;
	use Adepto\PredicaTree\Collection\Collection;
	use Adepto\PredicaTree\Predicate\Predicate;

	class SortProgram implements \JsonSerializable {
		const DYN_MARKER_APRIORI = '::';
		const DYN_MARKER_COLLECTION = '__'; // TODO differentiate between dynamic and static collection access

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

			return FancyArray::colonAccess($this->aprioriData, $key, self::DYN_MARKER_APRIORI);
		}

		public function getCollectionData($key = null) {
			if (is_null($key)) {
				return $this->collection->getAll();
			}

			return $this->collection->get($key);
		}

		public function getDynamicData($val) {
			if (is_array($val)) {
				return array_map([$this, 'getDynamicData'], $val);
			}

			if (!is_string($val)) {
				return $val;
			}

			$prefix = substr($val, 0, 2);
			$dataKey = substr($val, 2);

			if (strlen($dataKey) == 0) {
				$dataKey = null;
			}

			if ($prefix == self::DYN_MARKER_APRIORI) {
				return $this->getAprioriData($dataKey);
			} else if ($prefix == self::DYN_MARKER_COLLECTION) {
				return $this->getCollectionData($dataKey);
			}

			if (strpos($val, '\\') === 0) {
				$val = preg_replace('/^\\\\(::|__)/', '$1', $val);
			}

			return $val;
		}

		public function sort(): Collection {
			$applCollection = clone $this->collection;

			/** @var $predicate Predicate */
			foreach ($this->predicates as $predicate) {
				$identifiers = $predicate->getFullDynamicArguments();
				$dynamic = array_map([$this, 'getDynamicData'], $identifiers);
				$predicate->writeFullDynamicCache($dynamic);

				$predicate->apply($applCollection);
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