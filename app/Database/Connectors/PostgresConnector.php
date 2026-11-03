<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;

/**
 * Extends Laravel's Postgres connector so Neon TLS options reach the PDO DSN.
 *
 * Upstream only forwards sslmode/sslcert/sslkey/sslrootcert. Neon connection
 * strings also use channel_binding=require (SCRAM over TLS). When the installed
 * libpq supports it, the parameter is included; older libpq ignores unknown
 * keys or may error — see RENDER_DEPLOYMENT.md.
 */
class PostgresConnector extends BasePostgresConnector
{
    /**
     * Add the SSL / channel-binding options to the DSN.
     *
     * @param  string  $dsn
     * @param  array<string, mixed>  $config
     * @return string
     */
    protected function addSslOptions($dsn, array $config)
    {
        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert', 'channel_binding'] as $option) {
            if (isset($config[$option]) && $config[$option] !== '') {
                $dsn .= ";{$option}={$config[$option]}";
            }
        }

        return $dsn;
    }
}
