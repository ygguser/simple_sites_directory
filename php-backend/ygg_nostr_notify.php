<?php

function nostrNotify(string $msg, string $url, string $description): void
{
    // --- $sec must never be exposed, committed, or disclosed to anyone.
    $sec = 'nsec1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $relays = 'wss://nostr.twinkle.lol wss://nos.lol';

    $safeDescription = html_entity_decode(
        strip_tags($description),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $content = $msg
        . "\n" . $url
        . "\nDescription: " . $safeDescription
        . "\n";

    $relayList = preg_split('/\s+/', trim($relays));
    if ($relayList === false || $relayList === []) {
        error_log('nostrNotify: failed to parse relay list');
        return;
    }

    $relayList = array_values(array_filter(
        $relayList,
        static fn(string $v): bool => $v !== ''
    ));

    if ($relayList === []) {
        error_log('nostrNotify: relay list is empty');
        return;
    }

    $cmd = array_merge(
        [
            '/usr/local/bin/nak',
            'publish',
            '--sec',
            $sec,
            '--tag',
            't=yggdrasil',
        ],
        $relayList
    );

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $pipes = [];
    $env = ['HOME' => '/tmp'];
    $process = proc_open($cmd, $descriptorSpec, $pipes, null, $env);

    if (!is_resource($process)) {
        error_log('nostrNotify: failed to start /usr/local/bin/nak');
        return;
    }

    fwrite($pipes[0], $content);
    fclose($pipes[0]);

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        error_log(
            'nostrNotify: nak publish failed'
            . '; exit_code=' . $exitCode
            . '; stderr=' . trim((string)$stderr)
            . '; stdout=' . trim((string)$stdout)
        );
        return;
    }
}
