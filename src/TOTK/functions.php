<?php

/*
 * This file is a part of the TOTK Recipe Calculator project.
 *
 * Copyright (c) 2023-present Valithor Obsidion <valzargaming@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

if (PHP_OS_FAMILY == 'Windows') {
    function spawnChildProcess($cmd) { //Not tested
        execInBackground($cmd);
    }
    function execInBackground($cmd) {
        pclose(popen("start {$cmd}", "r")); //pclose(popen("start /B ". $cmd, "r"));;
    };
    function restart() {
        pclose(popen('cmd /c "'. getcwd() . '\run.bat"', "r")); //pclose(popen("start /B ". $cmd, "r"));;
    };
} else {
    function spawnChildProcess($cmd) {
        $process = new React\ChildProcess\Process("sudo $cmd");        
        $process->stdout->on('data', function ($chunk) {
            echo $chunk . PHP_EOL;
        });
        
        $process->stdout->on('end', function () {
            echo 'ended' . PHP_EOL;
        });
        
        $process->stdout->on('error', function (Exception $e) {
            echo 'error: ' . $e->getMessage() . PHP_EOL;
        });
        
        $process->stdout->on('close', function () {
            echo 'closed' . PHP_EOL;
        });
        
        $process->on('exit', function($exitCode, $termSignal) {
            if ($termSignal === null) {
                echo "Process exited with code $exitCode" . PHP_EOL;
            } else {
                echo "Process terminated with signal $termSignal" . PHP_EOL;
            }
        });
        return $process;
    }
    function execInBackground($cmd) {
        //exec("sudo $cmd > /dev/null &"); //Executes within the same shell
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $output = "sudo $cmd > /dev/null &";
        $proc = proc_open($output, $descriptorspec, $pipes);
        $proc_details = proc_get_status($proc);
        $pid = $proc_details['pid'];
        echo "Executing external shell command `$output` with PID $pid" . PHP_EOL;
    };
    function restart() {
        //exec("sudo nohup php main.php > botlog.txt &");
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $output = 'sudo nohup php main.php > botlog.txt &';
        $pid = proc_get_status(proc_open($output, $descriptorspec, $pipes))['pid'];
        echo "Executing external shell command `$output` with PID $pid" . PHP_EOL;
    };
}