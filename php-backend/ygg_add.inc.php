<?PHP

defined('yggEXEC') or die('Direct access to this file is prohibited.');

function page_end() {
    echo '</body></html>';
    exit(0);
}

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
echo '<html>';
echo '<head><title>Adding a site to the directory.</title>';
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
	
	if ((strlen($_POST['url']) > 500) || (strlen($_POST['url']) < 12) || (strlen($_POST['description']) < 3)) {
		echo 'Please fill in all fields of the form correctly.';
		page_end();
	}

	if($_POST['code'] != $_SESSION['code']) {
		echo 'Please verify that you typed in correct verification code.';
		page_end();
	}
} else {	
	echo 'Please fill in all fields of the form correctly.';
	page_end();
}

$dname = "";
if(isset($_POST['DomainName'])) {
    if ($_POST['DomainName'] != '' && strlen($_POST['DomainName']) < 3) {
        echo 'Domain name you have entered is incorrect!';
        page_end();
    }
    $dname = htmlspecialchars(trim(strip_tags(stripslashes($_POST['DomainName']))));
    $dname = str_replace(array("\n", "\r"), '', $dname);
    $dname = escapeshellcmd($dname);

    ////check domain resolv
    //if ($dname != '') {
    //    $output = preg_replace('/\n$/', '', shell_exec("dig AAAA @301:2923::53 $dname +short"));
    //    if ($output == '') {
    //        echo "The domain name cannot be resolved, it may not be registered yet. Try specifying it later.";
    //        page_end();
    //}   
    //else {
    //        if (strpos($_POST['url'], $output) === false) {
    //            echo "This domain name is associated with a different IP address ($output).";
    //            page_end();
    //        }
    //   }   
    //}
}

$url = htmlspecialchars(trim(strip_tags(stripslashes($_POST['url']))));
$url = str_replace(array("\n", "\r"), '', $url);
if (substr($url, -1) == ']') {
    $url = $url . '/'; 
}
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

if ($dname == '') {
    $query = 'SELECT ID FROM Sites WHERE URL LIKE \'' . preg_replace('{/$}', '', $url)  . '%\''; // preg_replace - to remove a slash at the end of a line
    $result = $db->query("$query");
    $nrows = 0;
    while ($result->fetchArray()) {
        $nrows++; break;
    }
    if ($nrows > 0) {
        echo 'The site is already contained in the catalog!';
        page_end();
    }
} else { // $dname != ''
    $query = 'SELECT ID FROM Sites WHERE ALFIS_DName=\'' . $dname . '\'';
    $result = $db->query("$query");
    $nrows = 0;
    while ($result->fetchArray()) {
        $nrows++; break;
    }
    if ($nrows > 0) {
        echo 'The site is already contained in the catalog!';
        page_end();
    }
}

$msgAddition=' After a while, it will appear in the list.<br>';
$available = '1';

$dt = date("Y-m-d\ H:i:s");

//add site to DB
$query = "INSERT INTO Sites (URL, Description, Available, Wyrd_DName, ALFIS_DName, AvailabilityDate, NumberOfChecks, NumberOfUnavailability) VALUES ('$url', '$description', '$available', '', '$dname', '$dt', 0, 0);";
if (!$db->exec("$query")) {
    echo 'Something went wrong. Please contact the site administrator.';
    page_end();
}
$query = "SELECT last_insert_rowid() AS SiteID;";
$result = $db->query("$query");
while ($row = $result->fetchArray()) {
    $siteID = "{$row['SiteID']}"; break;
}

if (isset($_POST['categories'])) {
    $categories_in_DB = array();
    $query = 'SELECT ID FROM Categories;';
    $result = $db->query("$query");
    while ($row = $result->fetchArray()) {
        $categories_in_DB[] = "{$row['ID']}";
    }
    $categ = $_POST['categories'];
    $categCount = count($categ);
    $query = '';
    if ($categCount > 0) {
        for($i = 0; $i < $categCount; $i++) {
            if (in_array($categ[$i], $categories_in_DB)) {
                $query .= "INSERT INTO SitesCategories (Site, Category) VALUES ('$siteID', '{$categ[$i]}');";
            }
        }
        $db->exec("$query");    
    }
}

echo "The site was successfully added!$msgAddition<a class=\"black\" href=\"/\">Return</a> to the main page.";

//regenerate HTML in background
list($scriptPath) = get_included_files();
$dir = dirname("$scriptPath");

exec('php -f \''. realpath("$dir/../php-backend/ygg_generateHTML.php") . '\' >/dev/null 2>&1 &');

// --- telegram notify
$scriptName = "$dir/../php-backend/ygg_telegram_notify.php";
if(file_exists("$scriptName")) {
    require_once("$scriptName");
    if (function_exists('tlgNotify')) {
        tlgNotify('A new site has been added:', $url, $description);
    }
}
// --- telegram notify

page_end();

?>
