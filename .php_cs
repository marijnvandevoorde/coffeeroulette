<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet;

$finder = Finder::create()
	->in([
		'src',
	]);

$rules = [
	'@Symfony' => true,
	'psr0' => true,
	'psr4' => true,
	'ordered_imports' => true,
	'phpdoc_align' => false,
	'phpdoc_to_comment' => false,
	'phpdoc_inline_tag' => false,
	'phpdoc_annotation_without_dot' => false,
	'yoda_style' => false,
	'blank_line_before_statement' => [
	    'statements' => ['continue', 'declare', 'return', 'throw', 'try']
	],
	'phpdoc_separation' => false,
	'concat_space' => [
		'spacing' => 'one',
	],
	'class_attributes_separation' => [
		'elements' => ['method', 'property'],
	],
	'pre_increment' => false,
	'increment_style' => false,
	'phpdoc_types' => false,
	'method_argument_space' => [
		'ensure_fully_multiline' => true,
	],
	'array_syntax' => [
		'syntax' => 'short',
	],
	'array_indentation' => true,
	'method_chaining_indentation' => true,
	'no_useless_else' => true,
];

$allowedRiskyRules = [
	'psr0',
	'psr4',
];

guardRiskyRules($rules, $allowedRiskyRules);

return Config::create()
	->setRules($rules)
	->setFinder($finder);

function guardRiskyRules(array $rules, array $allowedRiskyRules)
{
	$fixers = (new FixerFactory())
		->registerBuiltInFixers()
		->useRuleSet(new RuleSet($rules))
		->getFixers();

	$forbiddenRules = [];

	foreach ($fixers as $fixer) {
		if ($fixer->isRisky() && !in_array($fixer->getName(), $allowedRiskyRules)) {
			$forbiddenRules[] = $fixer->getName();
		}
	}

	if (!empty($forbiddenRules)) {
		throw new Exception("Risky rules ".implode(', ', $forbiddenRules)." not allowed");
	}
}
