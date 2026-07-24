<?php

declare(strict_types=1);

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The base test case is bound to all test files in the subdirectories
| listed below. Every test inherits from TestCase so it has access to
| the shared WordPress bootstrap setup.
|
*/

uses(TestCase::class)->in('Routes', 'Controllers', 'Views');
