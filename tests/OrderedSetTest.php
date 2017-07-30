<?php

	use Adepto\PredicaTree\Collection\OrderedSet;

	class OrderedSetTest extends PHPUnit_Framework_TestCase {
		/** @var $set OrderedSet */
		protected $set;

		protected function setUp() {
			$this->set = new OrderedSet([], 'int');
		}

		public function testStuff() {
			$this->set->add([2, 5, 7, 5, 3, 1, 9]);
			print_r(json_encode($this->set));
			
			$this->set->moveValue(3, 1);
			print_r(json_encode($this->set));
			$this->set->moveValue(3, 'first');
			print_r(json_encode($this->set));
			$this->set->moveValue(3, 'last');
			print_r(json_encode($this->set));
		}
	}