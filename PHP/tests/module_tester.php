<?php
require_once __DIR__ . '/../db_connect.php';

if (!function_exists('readline')) {
    function readline(string $prompt = '') {
        echo $prompt;
        $h = fopen('php://stdin', 'r');
        $line = fgets($h);
        return $line === false ? '' : trim($line);
    }
}

function runModuleTests(string $module) {
    $modulePath = __DIR__ . '/../' . $module;
    if (!file_exists($modulePath)) {
        echo "Module $module not found\n";
        return;
    }
    require_once $modulePath;
    $functions = array_values(array_filter(get_defined_functions()['user'], function ($fn) use ($modulePath) {
        $ref = new ReflectionFunction($fn);
        return realpath($ref->getFileName()) === realpath($modulePath);
    }));
    while (true) {
        echo "\nAvailable functions in $module:\n";
        foreach ($functions as $i => $fn) {
            echo ($i + 1) . ". $fn\n";
        }
        $choice = readline("Select function number or 'q' to quit: ");
        if ($choice === 'q') {
            break;
        }
        $index = (int)$choice - 1;
        if (!isset($functions[$index])) {
            echo "Invalid selection.\n";
            continue;
        }
        $fn = $functions[$index];
        $ref = new ReflectionFunction($fn);
        $args = [];
        foreach ($ref->getParameters() as $param) {
            if ($param->getName() === 'pdo' || ($param->hasType() && $param->getType() instanceof ReflectionNamedType && $param->getType()->getName() === 'PDO')) {
                $args[] = $GLOBALS['pdo'];
            } else {
                $val = readline("Enter {$param->getName()}: ");
                $args[] = $val;
            }
        }
        try {
            $result = $ref->invokeArgs($args);
            var_dump($result);
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
