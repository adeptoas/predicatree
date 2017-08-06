<?php
	namespace Adepto\PredicaTree\Program;

	use Adepto\Fancy\FancyArray;

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

			$this->memory = clone $subject;
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
				return $this->getSubjectData($dataKey);
			}

			if (strpos($val, '\\') === 0) {
				$markers = implode('|', array_map('preg_quote', self::DYN_MARKERS));
				$val = preg_replace('/^\\\\(' . $markers . ')/', '$1', $val);
			}

			return $val;
		}

		public function run() {
			if (!$this->hasRun) {
				$this->execute();
				$this->hasRun = true;
			}
		}

		public function rewind() {
			$this->subject = clone $this->memory;
			$this->hasRun = false;
		}

		public function getResult(bool $implyRun = true) {
			if ($implyRun && !$this->hasRun) {
				$this->run();
			}

			return $this->subject;
		}

		public abstract function getSubjectData($key = null);

		protected abstract function execute();

		public function jsonSerialize() {
			return [
				'apriori'		=>	$this->getAprioriData(),
				'subject'		=>	$this->getSubjectData(),
				'predicates'	=>	$this->getPredicates()
			];
		}
	}