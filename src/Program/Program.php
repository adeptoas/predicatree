<?php
	namespace Adepto\PredicaTree\Program;

	use Adepto\Fancy\FancyArray;
	use Adepto\PredicaTree\Predicate\Predicate;

	abstract class Program implements \JsonSerializable {
		const DYN_MARKER_APRIORI = '::';
		const DYN_MARKER_SUBJECT = '__';

		const DYN_MARKERS = [
			self::DYN_MARKER_APRIORI,
			self::DYN_MARKER_SUBJECT
		];

		protected $subject;
		protected $predicates;

		protected $aprioriData;

		protected $memory;
		protected $hasRun;

		public function __construct($subject, array $predicates, array $aprioriData = []) {
			$this->subject = $subject;
			$this->predicates = $predicates;

			$this->aprioriData = $aprioriData;

			$this->memory = self::cloneAny($subject);
			$this->hasRun = false;
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

		public function getSubject() {
			return $this->subject;
		}

		protected function getDynamicData($val) {
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
			} else if ($prefix == self::DYN_MARKER_SUBJECT) {
				return $this->accessSubjectData($dataKey);
			}

			if (strpos($val, '\\') === 0) {
				$markers = implode('|', array_map('preg_quote', self::DYN_MARKERS));
				$val = preg_replace('/^\\\\(' . $markers . ')/', '$1', $val);
			}

			return $val;
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

		public function run() {
			if (!$this->hasRun) {
				$this->execute();
				$this->hasRun = true;
			}
		}

		public function rewind() {
			$this->subject = self::cloneAny($this->memory);
			$this->hasRun = false;
		}

		public function getResult(bool $implyRun = true) {
			if ($implyRun && !$this->hasRun) {
				$this->run();
			}

			return $this->getFinalSubjectData();
		}

		// Hooks for subclasses

		protected function getFinalSubjectData() {
			return $this->getSubject();
		}

		protected function getSerializableSubjectData() {
			return $this->getSubject();
		}

		public abstract function accessSubjectData($key = null);

		public function jsonSerialize() {
			return array_filter([
				'apriori'		=>	$this->getAprioriData(),
				'subject'		=>	$this->getSerializableSubjectData(),
				'predicates'	=>	$this->getPredicates()
			]);
		}

		protected static function cloneAny($obj) {
			if (is_object($obj)) {
				return clone $obj;
			} else {
				$clone = $obj;
				return $clone;
			}
		}
	}