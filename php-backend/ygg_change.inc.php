<?PHP

defined('yggEXEC') or die('Direct access to this file is prohibited.');

// Load dependencies
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/helper.php';

function page_end() {
    echo '</body></html>';
    exit(0);
}

//function CheckCaptcha($code) {
//    if(isset($code)) {
//        if(($code != $_SESSION['code']) || (strlen($code) < 5)) {
//            return false;
//        }
//        unset($_SESSION['code']);
//        return true;
//    } else {
//        return false;
//    }
//}

function regen_and_notify($url, $description, $site_deletion = false) {
    //regenerate HTML in background
    list($scriptPath) = get_included_files();
    $dir = dirname("$scriptPath");

    exec('php -f \''. realpath("$dir/../php-backend/ygg_generateHTML.php") . '\' >/dev/null 2>&1 &');

    // --- telegram notify
    $scriptName = "$dir/../php-backend/ygg_telegram_notify.php";
    if(file_exists("$scriptName")) {
        require_once("$scriptName");
        if (function_exists('tlgNotify')) {
            $msg = 'Site data has been changed' . ($site_deletion === false ? '' : ' (deletion)') . ':';
            tlgNotify($msg, $url, $description);
        }
    }
    // --- telegram notify

    // --- matrix notify
    $scriptName = "$dir/../php-backend/ygg_matrix_notify.php";
    if(file_exists("$scriptName")) {
        require_once("$scriptName");
        if (function_exists('mtrxNotify')) {
            $msg = 'Site data has been changed' . ($site_deletion === false ? '' : ' (deletion)') . ':';
            mtrxNotify($msg, $url, $description);
        }
    }
    // --- matrix notify

}

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
echo '<html>';
echo '<head><title>Changing data is the directory.</title>';
echo '<link rel="SHORTCUT ICON" href="favicon.ico">';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>';
echo 'A.black:link {';
echo '    color: #000000;';
echo '}';
echo 'A.black:visited {';
echo '    color: #000000;';
echo '}';
echo 'A.black:hover {';
echo '    color: #ff0000;';
echo '}';
echo '</style>';
echo '</head><body style="background-color: #f5f5f0; font-family: sans-serif, Verdana, Arial, Helvetica;">';


$db = Helper::get_connection_to_db();
if(is_null($db)){
    echo '<br>';
    page_end();
}

session_start();

if(isset($_POST['submit'])) {

    $delete = false;
    if (!empty($_POST['deletesite'])) {
        $delete = true;
    }

	if ((strlen($_POST['url']) > 500) || (strlen($_POST['url']) < 12) || (strlen($_POST['description']) < 3 && !$delete)) {
		echo 'Please fill in all fields of the form correctly.';
		page_end();
	}

    //if (CheckCaptcha($_POST['code']) === false) {
    //    echo 'Please verify that you typed in correct verification code.';
    //    page_end();
    //}

    if($_SESSION['rndfname'] == '') {
        echo 'Incorrect form data.';
        echo $_SESSION['rndfname'];
        echo '<br>';
        page_end();
    }

}
else {
	echo 'Please fill in all fields of the form correctly.';
	page_end();
}

$url = htmlspecialchars(trim(strip_tags(stripslashes($_POST['url']))));
$url = str_replace(array("\n", "\r"), '', $url);
$description = htmlspecialchars(trim(strip_tags(stripslashes($_POST['description']))));
$description = str_replace(array("\n", "\r"), '', $description);

if (substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://' && substr($url, 0, 6) != 'ftp://') {
    echo 'The URL you have entered is incorrect!';
	page_end();
}

preg_match_all("/\[([^\]]*)\]/", $url, $matches);
$addr_OK = false;
$ip = reset($matches[1]);

if ($ip !== false ) {
    //if ((inet_pton($ip) !== false) && true) {
    if ((filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== FALSE) && preg_match('/^0{0,1}[2-3][a-f0-9]{0,2}:/', $ip)) {
		$addr_OK = true;
	}
}
if ($addr_OK === false) {
	echo 'The address you have entered is incorrect!';
	page_end();
}

$siteID = ''; $site_found = false;
try {
    $query = $db->prepare('SELECT ID AS SiteID FROM Sites WHERE URL = :url LIMIT 1;');
    $query->bindParam(':url', $url);
    $query->execute();
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $siteID = "{$row['SiteID']}"; $site_found = true; break;

    }
} catch (PDOException $e) {
    echo 'Something went wrong. Please contact the site administrator.';
    echo '<br>' . $e->getMessage() . '<br>';
    page_end();
}

if (!$site_found) {
    echo "The catalog does not contain a site with the address $url.";
	page_end();
}

// Checking if the file exists
if (!isset($_SESSION['rndfname'])) {
    echo "Can't get the random file name from _SESSION.";
    page_end();
}
$parsed_url = parse_url($url);
if ($parsed_url) {
    $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $url_to_check = "$scheme$host$port/" . $_SESSION['rndfname'];
} else {
    $url_to_check = "$url" . $_SESSION['rndfname'];
}
$urlHeaders = @get_headers($url_to_check);
// check the server response (it should be 200-OK)
if(!strpos($urlHeaders[0], '200')) {
    echo "The file <a href=\"$url_to_check\" class=\"black\" target=\"_blank\">$url_to_check</a> doesn't exist.";
    page_end();
}

// If the request is for deletion, delete
if ($delete) {
    try {
        $db->beginTransaction();
        $db->exec('PRAGMA foreign_keys = ON;');
        $query = $db->prepare('DELETE FROM Sites WHERE ID = :SiteID;');
        $query->bindParam(':SiteID', $siteID);
        $query->execute();
        $db->commit();
    } catch (PDOException $e) {
        echo 'Something went wrong. Please contact the site administrator.';
        echo '<br>' . $e->getMessage() . '<br>';
        page_end();
    }

    echo 'The deletion request was successfully completed. After a while, the site will disappear from the list.<br><a class="black" href="/">Return</a> to the main page.';
    regen_and_notify($url, $description, true);
    page_end();
}

// Parse address
$address = trim(
    (string)
    parse_url(
        $_POST['url'],
        PHP_URL_HOST
    ),
    '[]'
);

$dname = "";
if(isset($_POST['domain'])) {
    if ($_POST['domain'] != '' && strlen($_POST['domain']) < 3) {
        echo 'Domain name you have entered is incorrect!';
        page_end();
    }
    $dname = htmlspecialchars(trim(strip_tags(stripslashes($_POST['domain']))));
    $dname = str_replace(array("\n", "\r"), '', $dname);
    $dname = escapeshellcmd($dname);

    //check domain resolv
    if (!empty($dname)) {

        if ($addresses = Helper::dig($dname, DNS_YGG, DNS_DIG_TIME))
        {
            if (!in_array($address, $addresses))
            {
                echo sprintf(
                    'This domain name is associated with a different IP addresses (%s). Please correct it.',
                    implode(
                        ',',
                        $addresses
                    )
                );

                page_end();
            }
        }

        else
        {
            echo 'The domain name cannot be resolved, it may not be registered yet or it is not an <a href="https://github.com/Revertron/Alfis" target="_blank">ALFIS</a> domain name. Please correct it or don\'t specify it.';
            page_end();
        }
    }
}

$EmerDNS = "";
if(isset($_POST['EmerDNS'])) {
    if ($_POST['EmerDNS'] != '' && strlen($_POST['EmerDNS']) < 3) {
        echo 'Domain name you have entered is incorrect!';
        page_end();
    }
    $EmerDNS = htmlspecialchars(trim(strip_tags(stripslashes($_POST['EmerDNS']))));
    $EmerDNS = str_replace(array("\n", "\r"), '', $EmerDNS);
    $EmerDNS = escapeshellcmd($EmerDNS);

    //check domain resolv
    if (!empty($EmerDNS)) {

        if ($addresses = Helper::dig($EmerDNS, DNS_EMERCOIN, DNS_DIG_TIME))
        {
            if (!in_array($address, $addresses))
            {
                echo sprintf(
                    'This domain name is associated with a different IP addresses (%s). Please correct it.',
                    implode(
                        ',',
                        $addresses
                    )
                );

                page_end();
            }
        }

        else
        {
            echo 'The domain name cannot be resolved, it may not be registered yet or it is not an <a href="https://emercoin.com/en/documentation/blockchain-services/emerdns/emerdns-introduction/" target="_blank">EmerDNS</a> domain name. Please correct it or don\'t specify it.';
            page_end();
        }
    }
}

$dt = date("Y-m-d\ H:i:s");

//update record (first determine whether the categories are passed)
$categCount = 0;
if (isset($_POST['categories'])) {
    $categories_in_DB = array();
    try {
        $query = $db->query('SELECT ID FROM Categories;');
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $categories_in_DB[] = "{$row['ID']}";
        }
    } catch (PDOException $e) {
        echo 'Something went wrong. Please contact the site administrator.';
        echo '<br>' . $e->getMessage() . '<br>';
        page_end();
    }
    $categ = $_POST['categories'];
    $categCount = count($categ);
}

// update record
try {
    $db->beginTransaction();
    $query = $db->prepare('UPDATE Sites SET ALFIS_DName = :DName, EmerDNS = :EmerDNS, Description = :Description WHERE ID = :SiteID;');
    $query->bindParam(':SiteID', $siteID);
    $query->bindParam(':DName', $dname);
    $query->bindParam(':EmerDNS', $EmerDNS);
    $query->bindParam(':Description', $description);
    $query->execute();
    $query = $db->prepare('DELETE FROM SitesCategories WHERE Site = :SiteID;');
    $query->bindParam(':SiteID', $siteID);
    $query->execute();
    if ($categCount > 0) {
        for($i = 0; $i < $categCount; $i++) {
            if (in_array($categ[$i], $categories_in_DB)) {
                $query = $db->prepare('INSERT INTO SitesCategories (Site, Category) VALUES (:SiteID, :CategoryID)');
                $query->bindValue(":SiteID", $siteID, PDO::PARAM_INT);
                $query->bindValue(":CategoryID", $categ[$i], PDO::PARAM_INT);
                $query->execute();
            }
        }
    }
    $db->commit();
} catch (PDOException $e) {
    echo 'Something went wrong. Please contact the site administrator.';
    echo '<br>' . $e->getMessage() . '<br>';
    page_end();
}

$db = null;// close DB connection

echo 'The data was changed successfully! After a while, the list will be updated.<br>The file ' . $_SESSION['rndfname'] . ' can be deleted.<br><a class="black" href="/">Return</a> to the main page.';

regen_and_notify($url, $description);

page_end();

?>
