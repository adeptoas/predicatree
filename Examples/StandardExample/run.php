<?php
	namespace Examples\StandardExample;
	
	require __DIR__ . '/../../vendor/autoload.php';
	
	use Adepto\PredicaTree\Program\SortProgram;
	
	$DATA = [
		'apriori'   => [
			'key'   => 'value',
			'key1'   => 'value1'
		],
		'subject'    => [
			'type' => 'OrderedSet',
			'items'=> [1, 2, 3, 4, 5]
		],
		'predicates' => [
			[
				'if'   => [
					'condition' => 'EQUAL',
					'arguments' => [
						'::key',
						'value'
					]
				],
				'then' => [
					'action'    => 'collection',
					'arguments' => [
						'add',
						[6, 7, 8, 9, 10]
					]
				]
			],
			[
				'if'   => [
					'condition' => 'EQUAL',
					'arguments' => [
						'::key',
						'value'
					]
				],
				'then' => [
					'action'    => 'collection',
					'arguments' => [
						'remove',
						8
					]
				]
			]
		]
	];
	
	$program = SortProgram::build($DATA);
	var_dump($program->getResult());
