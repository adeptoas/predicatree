<?php
	require __DIR__ . '/../vendor/autoload.php';
	
	use Adepto\PredicaTree\Program\SortProgram;
	
	
	
	$data = [
		'apriori'       =>  [
			'clientshort'   =>      'de-kd',
			'officeid'      =>      '59'
		],
		'subject'    =>  [
			'type' =>   'OrderedSet',
			'items' =>   [],
		],
		'predicates'    =>  [
			[
				'if'    =>  [
					'condition' =>  'EQUAL',
					'arguments' =>  ['::clientshort', 'de-kd']
				],
				'then'  =>  [
					'action'    =>  'collection',
					'arguments' =>  ['add', [20, 4, 3, 9, 23]]
				]
			],
			[
				'if'    =>  [
					'condition' =>  'EQUAL',
					'arguments' =>  ['::officeid', 59]
				],
				'then'  =>  [
					'action'    =>  'collection',
					'arguments' =>  ['removeValue', 20]
				]
			]
		]
	];
	
	$program = SortProgram::build($data);
	
	$result = $program->getResult();
	var_dump($result);
	