<?php
	namespace Adepto\PredicaTree\Program;

	use Adepto\PredicaTree\Predicate\Predicate;

	class DecisionProgram extends Program {
		const KEY_HAS_RETURNED = 'has-returned';
		const KEY_RETURN_RESULT = 'return-result';

		public static function build(array $data): DecisionProgram {
			$predicates = Predicate::buildList($data['predicates']);

			return new self(
				$predicates,
				$data['apriori']
			);
		}

		/** @var array */
		protected $subject;

		public function __construct(array $predicates, array $aprioriData = []) {
			parent::__construct([
				self::KEY_HAS_RETURNED	=>	false,
				self::KEY_RETURN_RESULT	=>	null
			], $predicates, $aprioriData);
		}

		public function accessSubjectData($key = null) {
			if (is_null($key)) {
				return $this->subject;
			}

			return $this->subject[$key];
		}

		protected function getSerializableSubjectData() {
			return [];
		}

		protected function getFinalSubjectData() {
			return $this->accessSubjectData(self::KEY_RETURN_RESULT);
		}
	}