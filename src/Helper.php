<?php

namespace Flake;

const CLI_COLOR = [
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

if (!function_exists("dd")) {
    /** 
     * Dump data
     * 
     * @param mixed $data
     * @param bool $withCode
     * @param bool $withDump
     * @param bool $dumpThrowable
     * @param string $output
     * @param bool $return 
     * @return void
     */
    function dd($data, $withCode = true, $withDump = true, $dumpThrowable = true, $output = "php://stderr", bool $return = false)
    {
        if ($return) {
            $fd = fopen("php://output", "w+");
        } else {
            $fd = fopen($output, "a");
        }

        $trace = debug_backtrace(limit: 1);
        fwrite($fd, CLI_COLOR['cyan'] . "--[DUMP]------" . date('Y-m-d H:i:s') . '------[START]--' . CLI_COLOR['reset'] . "\n");
        if ($withCode) {
            fwrite($fd, CLI_COLOR['yellow'] . "--[CODE]----------------------------------------" . CLI_COLOR['reset'] . "\n");
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
                                    if (file_exists($lastException->getFile()))
                                        $file = file($lastException->getFile());
                                    else
                                        $file = [];
                                    $line_number = $lastException->getLine() - 1;
                                    goto post;
                                }
                            }
                        }
                    }
                }
                post:
                fwrite($fd, CLI_COLOR['red'] . "(Throwable) File: " . $lastException->getFile() . " on line " . $lastException->getLine() . CLI_COLOR['reset'] . "\n");
            } else {
                fwrite($fd, CLI_COLOR['green'] . "File: " . $trace[0]['file'] . " on line " . $trace[0]['line'] . CLI_COLOR['reset'] . "\n");
                $file = file($trace[0]['file']);
                $line_number = $trace[0]['line'] - 1;
            }

            df($file, $line_number);
        }
        if ($dumpThrowable && $data instanceof \Throwable) {
            fwrite($fd, CLI_COLOR['yellow'] . "--[THROWABLE]-----------------------------------------" . CLI_COLOR['reset'] . "\n");
            fwrite($fd, CLI_COLOR['red'] . 'Code: ' . $data->getCode() .  ', Message: ' . $data->getMessage() . CLI_COLOR['reset'] . "\n");
            fwrite($fd, CLI_COLOR['yellow'] . "--[TRACE]-----------------------------------------" . CLI_COLOR['reset'] . "\n");
            fwrite($fd, CLI_COLOR['red'] . trim(var_export(debug_backtrace(), true), "\n") . CLI_COLOR['reset'] . "\n");
        } elseif ($withDump) {
            fwrite($fd, CLI_COLOR['yellow'] . "--[VAR]-----------------------------------------" . CLI_COLOR['reset'] . "\n");
            if (is_object($data))
                fwrite($fd, CLI_COLOR['bold'] . get_class($data) . " #" . spl_object_id($data) . CLI_COLOR['reset'] . "\n");
            fwrite($fd, CLI_COLOR['magenta'] . trim(var_export($data, true), "\n") . CLI_COLOR['reset'] . "\n");
        }
        fwrite($fd, CLI_COLOR['cyan'] . "-----------------------------------------[END]--" . CLI_COLOR['reset'] . "\n");
        fclose($fd);

        if ($return) {
            $data = ob_get_contents();
            ob_end_clean();
            return $data;
        }
    }
}

if (!function_exists('df')) {
    function df(array $file, int $line_number = 0, $output = "php://stderr", $return = false)
    {
        if ($return) {
            ob_start();
            $fd = fopen("php://output", "a");
        } else {
            $fd = fopen($output, "a");
        }

        foreach (array_splice($file, max(0, $line_number - 3), min(7, count($file))) as $index => $line) {
            $prefix = $index + max(1, $line_number - 2);
            if ($line_number == $prefix - 1) {
                $prefix = str_repeat(">", strlen("{$line_number}"));
                fwrite($fd, CLI_COLOR['red'] . $prefix . ': ' . trim($line, "\n") . CLI_COLOR['reset'] . "\n");
            } else {
                $prefix = sprintf("%02d", $prefix);
                fwrite($fd, CLI_COLOR['blue'] . $prefix . ': ' . trim($line, "\n") . CLI_COLOR['reset'] . "\n");
            }
        }

        fclose($fd);
        if ($return) {
            $data = ob_get_contents();
            ob_end_clean();
            return $data;
        }
    }
}

if (!function_exists('cli_log')) {
    function cli_log($message, ...$context)
    {
        $fd = fopen("php://stderr", "a");
        fwrite($fd, '[LOG] ' . trim($message, "\n") . "\n");
        foreach ($context as $ctx) {
            fwrite($fd, $ctx  . "\n");
        }
        fclose($fd);
    }
}
