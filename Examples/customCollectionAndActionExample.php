<?php
	
	namespace Examples
	{
		require __DIR__ . '/../vendor/autoload.php';
		use DummyCollection\DummyCollection;
		use DummyCollection\DummyCollectionAction;
		
		const DATA = [
			'apriori'    => [
				'clientshort' => 'de-kd',
				'officeid'    => '59'
			],
			'subject'    => [
				'type' => DummyCollection::class
			],
			'predicates' => [
				[
					'if'   => [
						'condition' => 'EQUAL',
						'arguments' => [
							'::clientshort',
							'de-kd'
						]
					],
					'then' => [
						'action'    => DummyCollectionAction::class,
						'arguments' => [
							'add', [4, 1]
						]
					]
				]
			]
		];
	}
	
	namespace DummyCollection {
		use Adepto\PredicaTree\Predicate\Action\CustomAction;
		
		class DummyCollection {
			protected $elements;
			
			public function __construct() {
				$this->elements = [];
			}
			
			public function add($value) {
				$this->elements[] = $value;
				echo "succesful";
			}
		}
		
		class DummyCollectionAction extends CustomAction {
			
			protected function getCollectionClass(): string {
				return DummyCollection::class;
			}
		}
	}
	