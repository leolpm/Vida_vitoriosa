<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;
use InvalidArgumentException;

class ServeCommand extends BaseServeCommand
{
    /**
     * Get the port for the command.
     */
    protected function port()
    {
        $defaultPort = (int) config('vida.dev_server_port', 8888);

        if (! isset($this->input)) {
            return $defaultPort + $this->portOffset;
        }

        try {
            $port = $this->input->getOption('port');
        } catch (InvalidArgumentException) {
            return $defaultPort + $this->portOffset;
        }

        if (is_null($port)) {
            [, $port] = $this->getHostAndPort();
        }

        $port = $port ?: $defaultPort;

        return $port + $this->portOffset;
    }
}
