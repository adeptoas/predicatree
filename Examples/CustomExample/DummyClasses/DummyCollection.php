<?php
	namespace Examples\CustomExample\DummyClasses;
	
	use Examples\CustomExample\DummyClasses\Apple;
	
	class DummyCollection {
		protected $elements;
		
		public function __construct() {
			$this->elements = [];
		}
		
		public function add(Apple $value) {
			$this->elements[] = $value;
			echo "succesful\n";
		}
	}