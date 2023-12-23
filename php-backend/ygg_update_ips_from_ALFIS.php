<?php

require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/../config.php';

//test:
//URLS=$(sqlite3 /var/www/YggSites/database.db "SELECT url FROM sites LIMIT 5;"); php -f ygg_update_ips_from_ALFIS.php $URLS

$db = Helper::get_connection_to_db(__DIR__ . '/../database.db');
if(is_null($db)) {
    exit(1);
}

try {
    $urls='';
    $pieces = explode("\n", $argv[1]);
    foreach ($pieces as &$val_url) {
        $urls = $urls . '\'' . $val_url  . '\',';
    }
    $urls = substr($urls, 0, strlen($urls)-1);

    $query = $db->query("SELECT ID, URL, ALFIS_DName, meshname FROM Sites WHERE URL IN ($urls) AND ALFIS_DName != '';");
    while ($row = $query->fetch()) {
        //echo $row['URL']. ' ' . $row['ALFIS_DName'] ."\n";
        $address = trim((string)parse_url($row['URL'], PHP_URL_HOST), '[]');
        //echo "$address\n";
        if ($addresses = Helper::dig($row['ALFIS_DName'], DNS_YGG, DNS_DIG_TIME)) {
            if (!in_array($address, $addresses)) {
                if(!empty($addresses)) {
                    $newip = $addresses[0];
                    if (filter_var($newip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== FALSE && preg_match('/^0{0,1}[2-3][a-f0-9]{0,2}:/', $newip)) {
                        //echo 'Old: ' . $row['URL'] . '; ';
                        updateURL($db, $row['ID'], str_replace($address, $newip, $row['URL']), $row['meshname'], $row['URL']);
                    } else {
                        echo $row['URL'] . "\n";
                    }
                } else {
                    echo $row['URL'] . "\n";
                }
            } else {
                echo $row['URL'] . "\n";
            }
        } else {
            echo $row['URL'] . "\n";
        }
    }
    try {
        $query = $db->query("SELECT ID, URL, ALFIS_DName, meshname FROM Sites WHERE URL IN ($urls) AND ALFIS_DName='';");
    } catch (PDOException $e){
        throw $e;
    }
    while ($row = $query->fetch()) {
        echo $row['URL'] . "\n";
    }
} catch (PDOException $e) {
    echo 'The request could not be completed. ';
    echo $e->getMessage(); exit(1);
    //err_exit();
}

function updateURL($db, $ID, $newURL, $old_meshname, $old_URL) {
    //echo "New: $newURL\n";
    
    //meshname
    $meshname = Helper::url_as_meship($newURL);

    //echo "New meship: $meshname\n";
    //echo "ID: $ID\n";

    try {
        if (($meshname == $old_meshname) || ($meshname == '')) {
            $query = $db->prepare("UPDATE Sites SET URL=:NewURL WHERE ID=:SiteID");
        } else {
            $query = $db->prepare("UPDATE Sites SET URL=:NewURL, meshname=:New_meshname WHERE ID=:SiteID");
            $query->bindValue(':New_meshname', $meshname);
        }
        $query->bindValue(':NewURL', $newURL);
        $query->bindValue(':SiteID', $ID);
        $query->execute();
    } catch (PDOException $e) {
        echo 'The request could not be completed. ';
        echo $e->getMessage(); exit(1);
        //echo "$old_URL\n";
    }
    echo "$newURL\n";
}

?>
