<?php

if (!function_exists("dd")) {
    /** 
     * Dump data
     * 
     * @param mixed $data
     * @param bool $withCode
     * @param bool $withDump
     * @param string $output
     * @return void
     */
    function dd($data, $withCode = true, $withDump = true, $output = "php://stderr")
    {
        $colors = [
            'reset' => "\033[0m",
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'magenta' => "\033[35m",
            'cyan' => "\033[36m",
            'white' => "\033[37m",
            'bold' => "\033[1m",
        ];

        $fd = fopen($output, "w");
        $trace = debug_backtrace(limit: 1);
        fwrite($fd, $colors['cyan'] . "--[DUMP]----------------------------------------" . $colors['reset'] . "\n");
        if ($withCode) {
            fwrite($fd, $colors['yellow'] . "--[CODE]----------------------------------------" . $colors['reset'] . "\n");
            fwrite($fd, $colors['green'] . "File: " . $trace[0]['file'] . " on line " . $trace[0]['line'] . $colors['reset'] . "\n");
            $file = file($trace[0]['file']);
            $line_number = $trace[0]['line'] - 1;

            foreach (array_splice($file, max(0, $line_number - 3), min(7, count($file))) as $index => $line) {
                $prefix = $index + max(1, $line_number - 2);
                if ($line_number == $prefix - 1) {
                    $prefix = ">>";
                    fwrite($fd, $colors['red'] . $prefix . ': ' . trim($line, "\n") . $colors['reset'] . "\n");
                } else {
                    $prefix = sprintf("%02d", $prefix);
                    fwrite($fd, $colors['blue'] . $prefix . ': ' . trim($line, "\n") . $colors['reset'] . "\n");
                }
            }
        }
        if ($withDump) {
            fwrite($fd, $colors['yellow'] . "--[VAR]-----------------------------------------" . $colors['reset'] . "\n");
            fwrite($fd, $colors['magenta'] . trim(var_export($data, true), "\n") . $colors['reset'] . "\n");
        }
        fwrite($fd, $colors['cyan'] . "-----------------------------------------[END]--" . $colors['reset'] . "\n");
        fclose($fd);
    }
}

if (!function_exists("make")) {
    /**
     * @template T
     * @param class-string<T> $class
     * @param array $options
     * @return T
     */
    function make($class, $options = [])
    {
        $class = str_replace("/", "\\", $class);
        $container = \Flake\ApplicationContext::getContainer();
        if ($container->has($class)) {
            return $container->get($class);
        } else if (class_exists($class)) {
            if ($container instanceof \Flake\Container) {
                $container->set($class, new $class(...$options));
            }
            return $container->get($class);
        }
        return null;
    }
}
