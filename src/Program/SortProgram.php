<?php
	namespace Adepto\PredicaTree\Program;

	use Adepto\PredicaTree\Collection\Collection;
	use Adepto\PredicaTree\Predicate\Predicate;

	class SortProgram extends Program {
		const DYN_MARKER_APRIORI = '::';
		const DYN_MARKER_COLLECTION = '__';

		public static function build(array $data): SortProgram {
			$apriori = $data['apriori'];

			$collection = Collection::fromSpecifiedArray($data['subject']);
			$predicates = Predicate::buildList($data['predicates']);

			return new self(
				$collection,
				$predicates,
				$apriori
			);
		}

		public function __construct(Collection $collection, array $predicates, array $aprioriData = []) {
			parent::__construct($collection, $predicates, $aprioriData);
		}

		protected function execute() {
			/** @var $predicate Predicate */
			foreach ($this->predicates as $predicate) {
				$identifiers = $predicate->getFullDynamicArguments();
				$dynamic = array_map([$this, 'getDynamicData'], $identifiers);
				$predicate->writeFullDynamicCache($dynamic);

				$predicate->apply($this->subject);
			}
		}

		public function getSubjectData($key = null) {
			if (is_null($key)) {
				return $this->subject->getAll();
			}

			return $this->subject->get($key);
		}

		public function jsonSerialize() {
			return array_merge(parent::jsonSerialize(), [
				'subject'	=>	$this->subject
			]);
		}
	}