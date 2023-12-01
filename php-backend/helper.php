<?php

class Helper
{
    // Return resolved IPv6 address or null
    public static function dig(string $host, array $dns) : ?string
    {
        // Dig host for each provider until success
        foreach ($dns as $provider)
        {
            // Convert response to string
            $response = (string) shell_exec(
                sprintf(
                    'dig AAAA @%s %s +short',
                    $provider,
                    $host
                )
            );

            // Remove tabulation
            $response = trim(
                $response
            );

            // Make sure it's valid IPv6
            if (filter_var($response, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            {
                return $response;
            }
        }

        return null;
    }
}