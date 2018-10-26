<?php
	require_once __DIR__ . '/customCollectionAndActionExample.php';
	use Adepto\PredicaTree\Program\SortProgram;
	use DummyCollection\DummyCollection;
	use DummyCollection\DummyCollectionAction;
	
	$program = SortProgram::build(\Examples\DATA);
	$program->getResult();