<?php

namespace Flake;

if (!function_exists("dd")) {
    /** 
     * Dump data
     * 
     * @param mixed $data
     * @param bool $withCode
     * @param bool $withDump
     * @param bool $dumpThrowable
     * @param string $output
     * @return void
     */
    function dd($data, $withCode = true, $withDump = true, $dumpThrowable = true, $output = "php://stderr")
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
        fwrite($fd, $colors['cyan'] . "--[DUMP]------" . date('Y-m-d H:i:s') . '------[START]--' . $colors['reset'] . "\n");
        if ($withCode) {
            fwrite($fd, $colors['yellow'] . "--[CODE]----------------------------------------" . $colors['reset'] . "\n");
            if ($data instanceof \Throwable) {
                $foundSource = false;
                $lastException = $data;
                $file = file($lastException->getFile());
                $line_number = $lastException->getLine() - 1;
                // Try to find the real source
                // TODO: too much work here, maybe a better way?
                foreach (debug_backtrace() as $trace) {
                    $args = $trace['args'];
                    foreach ($args as $arg) {
                        if ($arg instanceof \Throwable) {
                            $lastException = $arg;
                            if ($data->getMessage() == $arg->getMessage()) {
                                $foundSource = true;
                                while ($arg = $arg->getPrevious()) {
                                    $lastException = $arg;
                                    if ($arg->getMessage() == $data->getMessage()) {
                                        break;
                                    }
                                }

                                if ($foundSource) {
                                    $file = file($lastException->getFile());
                                    $line_number = $lastException->getLine() - 1;
                                    goto post;
                                }
                            }
                        }
                    }
                }
                post:
                fwrite($fd, $colors['red'] . "(Throwable) File: " . $lastException->getFile() . " on line " . $lastException->getLine() . $colors['reset'] . "\n");
            } else {
                fwrite($fd, $colors['green'] . "File: " . $trace[0]['file'] . " on line " . $trace[0]['line'] . $colors['reset'] . "\n");
                $file = file($trace[0]['file']);
                $line_number = $trace[0]['line'] - 1;
            }

            foreach (array_splice($file, max(0, $line_number - 3), min(7, count($file))) as $index => $line) {
                $prefix = $index + max(1, $line_number - 2);
                if ($line_number == $prefix - 1) {
                    $prefix = str_repeat(">", strlen("{$line_number}"));
                    fwrite($fd, $colors['red'] . $prefix . ': ' . trim($line, "\n") . $colors['reset'] . "\n");
                } else {
                    $prefix = sprintf("%02d", $prefix);
                    fwrite($fd, $colors['blue'] . $prefix . ': ' . trim($line, "\n") . $colors['reset'] . "\n");
                }
            }
        }
        if ($dumpThrowable && $data instanceof \Throwable) {
            fwrite($fd, $colors['yellow'] . "--[THROWABLE]-----------------------------------------" . $colors['reset'] . "\n");
            fwrite($fd, $colors['red'] . 'Code: ' . $data->getCode() .  ', Message: ' . $data->getMessage() . $colors['reset'] . "\n");
            fwrite($fd, $colors['yellow'] . "--[TRACE]-----------------------------------------" . $colors['reset'] . "\n");
            fwrite($fd, $colors['red'] . trim(var_export(debug_backtrace(), true), "\n") . $colors['reset'] . "\n");
        } elseif ($withDump) {
            fwrite($fd, $colors['yellow'] . "--[VAR]-----------------------------------------" . $colors['reset'] . "\n");
            if (is_object($data))
                fwrite($fd, $colors['bold'] . get_class($data) . " #" . spl_object_id($data) . $colors['reset'] . "\n");
            fwrite($fd, $colors['magenta'] . trim(var_export($data, true), "\n") . $colors['reset'] . "\n");
        }
        fwrite($fd, $colors['cyan'] . "-----------------------------------------[END]--" . $colors['reset'] . "\n");
        fclose($fd);
    }
}
