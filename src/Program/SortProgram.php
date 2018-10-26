<?php
	namespace Adepto\PredicaTree\Program;

	use Adepto\PredicaTree\Collection\Collection;
	use Adepto\PredicaTree\Predicate\Predicate;

	class SortProgram extends Program {
		const DYN_MARKER_APRIORI = '::';
		const DYN_MARKER_COLLECTION = '__';

		public static function build(array $data): SortProgram {
			$apriori = $data['apriori'];
			
			if (class_exists($data['subject']['type'])) {
				//user implemented class
				$collection = new $data['subject']['type']();
			} else {
				//one of our collection classes.
				$collection = Collection::fromSpecifiedArray($data['subject']);
			}
			
			$predicates = Predicate::buildList($data['predicates']);

			return new self(
				$collection,
				$predicates,
				$apriori
			);
		}

		protected $subject;

		public function __construct($collection, array $predicates, array $aprioriData = []) {
			parent::__construct($collection, $predicates, $aprioriData);
		}

		public function accessSubjectData($key = null) {
			if (is_null($key)) {
				return $this->subject->getAll();
			}

			return $this->subject->get($key);
		}
	}