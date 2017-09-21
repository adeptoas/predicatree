<?php
	namespace Adepto\PredicaTree\Predicate\Action;

	use Adepto\PredicaTree\Predicate\Action;
	use Adepto\PredicaTree\Predicate\HigherOrderCacheObject;

	class ChainAction extends Action {
		use HigherOrderCacheObject;

		public function apply(&$subject) {
			/** @var $child Action */
			foreach ($this->getChildren() as $child) {
				$child->apply($subject);
			}
		}
	}