<?php

namespace Ssh;

use RuntimeException;

/**
 * Wrapper for ssh2_exec
 *
 * @author Cam Spiers <camspiers@gmail.com>
 * @author Greg Militello <junk@thinkof.net>
 */
class Exec extends Subsystem
{
    protected function createResource()
    {
        $this->resource = $this->getSessionResource();
    }

    public function run($cmd, $pty = null, array $env = array(), $width = 80, $height = 25, $width_height_type = SSH2_TERM_UNIT_CHARS)
    {
        list($output, $error_output) = $this->runRaw($cmd, $pty, $env, $width, $height, $width_height_type);
        $match = preg_match("/^(.*)\n(0|-?[1-9][0-9]*)$/s", $output, $matches);

        if ( ! $match) {
            throw new RuntimeException("Output didn't contain return status.");
        }

        list(, $output, $error_code) = $matches;

        if ($error_code !== "0") {
            throw new RuntimeException($error_output, (int) $error_code);
        }

        return $output;
    }

    private function runRaw($cmd, $pty, $env, $width, $height, $width_height_type) {
        $cmd .= ';echo -en "\n$?"';
        $stdout = ssh2_exec($this->getResource(), $cmd, $pty, $env, $width, $height, $width_height_type);
        $stderr = ssh2_fetch_stream($stdout, SSH2_STREAM_STDERR);
        stream_set_blocking($stderr, true);
        stream_set_blocking($stdout, true);
        $error_output = stream_get_contents($stderr);
        $output = stream_get_contents($stdout);

        return array($output, $error_output);
    }
}
