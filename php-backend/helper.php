<?php

class Helper
{
    // Return resolved IPv6 address or null
    public static function dig(string $host, array $dns, int $time = 5) : ?array
    {
        // Dig host for each provider until success
        foreach ($dns as $provider)
        {
            // Convert response to string
            $response = (string) shell_exec(
                sprintf(
                    'dig AAAA @%s %s +short +time=%s',
                    $provider,
                    $host,
                    $time
                )
            );

            // Validate AAAA for each line returned
            $addresses = [];

            foreach ((array) explode(PHP_EOL, $response) as $ip)
            {
                // Make sure it's valid IPv6
                if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
                {
                    $addresses[] = $ip;
                }
            }

            // Valid addresses collected, stop to check other DNS
            if ($addresses)
            {
                return $addresses;
            }
        }

        return null;
    }

    public static function url_as_meship(string $url) {
        $meshname = '';
        require_once __DIR__ . '/base32.php';
        $parsed_url = parse_url($url);
        if ($parsed_url) {
            $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
            $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
            $url_path = (isset($parsed_url['path']) && $parsed_url['path'] != '/') ? $parsed_url['path'] : '';
            $meshname = base32_encode(inet_pton(str_replace('[', '' , str_replace(']', '', $host)))) . '.meship' . "$port$url_path";
        }
        return $meshname;
    }

    public static function get_connection_to_db(string $path_to_db_file = '') {
        $db = NULL;

        $db_file = $path_to_db_file == '' ? DB_FILE : $path_to_db_file;
        if (!file_exists($db_file)) {
            echo 'The DB file doesn\'t exist!';
            //echo "\n"; echo 'Current dir: ' . __DIR__;
            //echo "\nPath to DB: $db_file\n";
            return NULL;
        }

        try {
            $db = new PDO("sqlite:$db_file");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Can\'t open database! ';
            echo $e->getMessage();
        }

        return $db;
    }

}
