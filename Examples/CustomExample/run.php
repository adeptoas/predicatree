<?php
	namespace Examples\CustomExample;
	
	require __DIR__ . '/../../vendor/autoload.php';
	
	use Adepto\PredicaTree\Program\SortProgram;
	use Examples\CustomExample\DummyClasses\DummyCollectionAction;
	use Examples\CustomExample\DummyClasses\DummyCollection;
	use Examples\CustomExample\DummyClasses\Apple;
	
	$DATA = [
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
						'add',
						new Apple()
					]
				]
			]
		]
	];
	
	$program = SortProgram::build($DATA);
	var_dump($program->getResult());