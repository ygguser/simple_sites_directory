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
}