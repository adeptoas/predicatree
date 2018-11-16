<?php
	namespace Examples\CustomExample\DummyClasses;
	
	use Adepto\PredicaTree\Predicate\Action\CustomAction;
	use Examples\CustomExample\DummyClasses\DummyCollection;
	
	class DummyCollectionAction extends CustomAction {
		
		protected function getCollectionClass(): string {
			return DummyCollection::class;
		}
	}