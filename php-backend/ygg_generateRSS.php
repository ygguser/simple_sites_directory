<?php

// Prevent multi-thread execution
$semaphore = sem_get(crc32('ygg_generateRSS'), 1);

if (false === sem_acquire($semaphore, true))
{
    echo _('Process already running in another thread!') . PHP_EOL;

    exit;
}

// Load dependencies
require_once __DIR__ . '/../config.php';

// Init helpers
function generateRSS(
    string $name,
    array $sites
): bool
{
    $rss[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $rss[] = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">';
    $rss[] = '<channel>';

    $rss[] = sprintf(
        '<atom:link href="%s" rel="self" type="application/rss+xml"></atom:link>',
        WEBSITE_URL
    );

    $rss[] = sprintf(
        '<link>%s</link>',
        WEBSITE_URL
    );

    $rss[] = sprintf(
        '<title>%s (%s)</title>',
        htmlspecialchars(
            WEBSITE_NAME,
            ENT_QUOTES,
            'UTF-8'
        ),
        _($name)
    );

    foreach ($sites as $site)
    {
        $rss[] = '<item>';

        // title
        $rss[] = sprintf(
            '<title>%s</title>',
            htmlspecialchars(
                $site['Description'],
                ENT_QUOTES,
                'UTF-8'
            )
        );

        // description
        $rss[] = '<description>';

        $description = [];

        if ($site['Description'])
        {
            $description[] = $site['Description'];
        }

        if ($site['URL'])
        {
            $description[] = $site['URL'];
        }

        if ($site['ALFIS_DName'])
        {
            $description[] = $site['ALFIS_DName'];
        }

        if ($site['EmerDNS'])
        {
            $description[] = $site['ALFIS_DName'];
        }

        if ($site['meshname'])
        {
            $description[] = $site['meshname'];
        }

        $rss[] = htmlspecialchars(
            implode(
                PHP_EOL . '<br>' . PHP_EOL, // BR needed to allow Thunderbird make line breaks
                $description
            ),
            ENT_QUOTES,
            'UTF-8'
        );

        $rss[] = '</description>';

        // pubDate
        $rss[] = sprintf(
            '<pubDate>%s</pubDate>',
            date(
                'r',
                strtotime(
                    $site['AvailabilityDate']
                )
            )
        );

        // guid
        $rss[] = sprintf(
            '<guid>%s#%s</guid>',
            WEBSITE_URL,
            $site['ID']
        );

        $rss[] = sprintf(
            '<link>%s</link>',
            $site['URL']
        );

        $rss[] = '</item>';
    }

    $rss[] = '</channel>';

    $rss[] = '</rss>';

    // Save to FS
    @mkdir(
        __DIR__ . '/../site/rss',
        0755,
        true
    );

    return file_put_contents(
        sprintf(
            __DIR__ . '/../site/rss/%s.xml',
            strtolower(
                trim(
                    preg_replace(
                        '/\W/ui',
                        '-',
                        $name
                    ),
                    '-'
                )
            )
        ),
        implode(
            PHP_EOL,
            $rss
        )
    );
}

// Connect DB
try
{
    $db = new SQLite3(
        __DIR__ . '/../database.db',
        SQLITE3_OPEN_READONLY
    );
}

catch (Exception $exception)
{
    var_dump($exception);

    exit;
}

// Generate common websites list

echo _('Generating common RSS...') . PHP_EOL;

if ($querySites = $db->query('SELECT * FROM `Sites` ORDER BY `ID` DESC LIMIT ' . (int) RSS_LIMIT))
{
    $sites = [];
    while ($site = $querySites->fetchArray(SQLITE3_ASSOC))
    {
        $sites[] = $site;
    }

    if (generateRSS('All', $sites))
    {
        echo _('Common RSS successfully generated!') . PHP_EOL;
    }

    else
    {
        echo _('Common RSS generation failed!') . PHP_EOL;
    }
}

// Generate category-based websites list

echo _('Generating categories RSS...') . PHP_EOL;

if ($queryCategories = $db->query('SELECT * FROM `Categories`'))
{
    while ($category = $queryCategories->fetchArray(SQLITE3_ASSOC))
    {
        if ($querySites = $db->query('SELECT * FROM  `Sites`
                                               JOIN  `SitesCategories` ON (
                                                        `SitesCategories`.`Site` = `Sites`.`ID` AND
                                                        `SitesCategories`.`Category` = ' . (int) $category['ID'] . '
                                               )
                                               ORDER BY `Sites`.`ID` DESC LIMIT ' . (int) RSS_LIMIT))
        {
            $sites = [];
            while ($site = $querySites->fetchArray(SQLITE3_ASSOC))
            {
                $sites[] = $site;
            }

            if (generateRSS($category['Name'], $sites))
            {
                echo sprintf(
                    _('RSS for category "%s" successfully generated!') . PHP_EOL,
                    $category['Name']
                );
            }

            else
            {
                echo sprintf(
                    _('RSS for category "%s" generation failed!') . PHP_EOL,
                    $category['Name']
                );
            }
        }
    }
}