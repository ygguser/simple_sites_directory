<?PHP

defined('yggEXEC') or die('Direct access to this file is prohibited.');

function page_end() {
    echo '</body></html>';
    exit(0);
}

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

try {
    $db = new SQLite3('./../database.db', SQLITE3_OPEN_READWRITE);
} catch (Exception $exception) {
    echo 'Can\'t open database!';
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

	if($_POST['code'] != $_SESSION['code']) {
		echo 'Please verify that you typed in correct verification code.';
		page_end();
    }

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
//echo $ip;
//echo var_dump($matches);
if ($ip !== false ) {
	if ((inet_pton($ip) !== false) && true) {
		$addr_OK = true;
	}
}
if ($addr_OK === false) {
	echo 'The address you have entered is incorrect!';
	page_end();
}

$query = "SELECT ID FROM Sites WHERE URL = '$url' LIMIT 1;";
$result = $db->query("$query");
$site_found = false;
$siteID = '';
while ($row = $result->fetchArray()) {
    $site_found = true;
    $siteID = "{$row['ID']}"; 
    $site_found = true; break;
}
if (!$site_found) {
    echo "The catalog does not contain a site with the address $url.";
	page_end();
}

// Checking if the file exists
if (substr("$url", -1) == '/') {
    $urltocheck = "$url" . $_SESSION['rndfname'];
} else {
    $urltocheck = "$url" . '/' . $_SESSION['rndfname'];
}
$urlHeaders = @get_headers($urltocheck);
// check the server response (it should be 200-OK)
if(!strpos($urlHeaders[0], '200')) {
    echo "The file <a href=\"$urltocheck\" class=\"black\" target=\"_blank\">$urltocheck</a> doesn't exist.";
    page_end();
}

// If the request is for deletion, delete
if ($delete) {
    $query = "PRAGMA foreign_keys = ON; DELETE FROM Sites WHERE ID = '$siteID';";
    $db->exec("$query");
    echo 'The deletion request was successfully completed. After a while, the site will disappear from the list.<br><a class="black" href="/">Return</a> to the main page.';
    regen_and_notify($url, $description, true);
    page_end();
}

$dname = htmlspecialchars(trim(strip_tags(stripslashes($_POST['domain']))));
$dname = str_replace(array("\n", "\r"), '', $dname);
$dname = escapeshellcmd($dname);

////check domain resolv
//if ($dname != '' && !$delete) {
//    $output = preg_replace('/\n$/', '', shell_exec("dig AAAA @301:2923::53 $dname +short"));
//    if ($output == '') {
//        echo "The domain name cannot be resolved, it may not be registered yet. Try specifying it later.";
//        page_end();
//    }
//    else {
//        if (strpos($url, $output) === false) {
//            echo "This domain name is associated with a different IP address ($output).";
//            //echo "URL: $url<br>";
//            //echo "IP: $output<br>";
//            //echo "DOMAIN: $dname<br>";
//            page_end();
//        }
//    }
//}

$available = "1";

$dt = date("Y-m-d\ H:i:s");

// update record
$query = "BEGIN TRANSACTION; UPDATE Sites SET ALFIS_DName = '$dname', Description = '$description' WHERE ID = '$siteID'; DELETE FROM SitesCategories WHERE Site = '$siteID';";

// update categories
if (isset($_POST['categories'])) {
    $categories_in_DB = array();
    $query_c = 'SELECT ID FROM Categories;';
    $result = $db->query("$query_c");
    while ($row = $result->fetchArray()) {
        $categories_in_DB[] = "{$row['ID']}";
    }
    $categ = $_POST['categories'];
    $categCount = count($categ);
    if ($categCount > 0) {
        for($i = 0; $i < $categCount; $i++) {
            if (in_array($categ[$i], $categories_in_DB)) {
                $query .= "INSERT INTO SitesCategories (Site, Category) VALUES ('$siteID', '{$categ[$i]}');";
            }
        }
    }
}
$query .= " COMMIT;";
if (!$db->exec("$query")) {
    echo 'Something went wrong. Please contact the site administrator.';
    page_end();
}

echo 'The data was changed successfully! After a while, the list will be updated.<br>The file ' . $_SESSION['rndfname'] . ' can be deleted.<br><a class="black" href="/">Return</a> to the main page.';

regen_and_notify($url, $description);

page_end();

?>
