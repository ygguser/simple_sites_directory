<?PHP

defined('yggEXEC') or die('Direct access to this file is prohibited.');

function page_end() {
    echo '</body></html>';
    exit(0);
}

function CheckCaptcha($code) {
    if(isset($code)) {
        if(($code != $_SESSION['code']) || (strlen($code) < 5)) {
            return false;
        }
        unset($_SESSION['code']);
        return true;
    } else {
        return false;
    }
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

$db_file= './../database.db';
if (!file_exists("$db_file")) {
    echo 'The DB file doesn\'t exist!';
    page_end();
}
try {
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { 
    echo 'Can\'t open database!<br>';
    echo $e->getMessage();
    page_end();
}

session_start();
 
if(isset($_POST['submit'])) {
	
	if ((strlen($_POST['url']) > 500) || (strlen($_POST['url']) < 12) || (strlen($_POST['description']) < 3)) {
		echo 'Please fill in all fields of the form correctly.';
		page_end();
	}

    if (CheckCaptcha($_POST['code']) === false) {
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

    //check domain resolv
    if ($dname != '') {
        $output = preg_replace('/\n$/', '', shell_exec("dig AAAA @302:db60::53 $dname +short"));
        if ($output == '') {
            echo 'The domain name cannot be resolved, it may not be registered yet or it is not an <a href="https://github.com/Revertron/Alfis" target="_blank">ALFIS</a> domain name. Please correct it or don\'t specify it.';
            page_end();
        }   
        else {
            if (strpos($_POST['url'], $output) === false) {
                echo "This domain name is associated with a different IP address ($output). Please correct it.";
                page_end();
            }
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
    if ($EmerDNS != '') {
        $output = preg_replace('/\n$/', '', shell_exec("dig AAAA @seed1.emercoin.com $EmerDNS +short"));
        if ($output == '') {
            echo 'The domain name cannot be resolved, it may not be registered yet or it is not an <a href="https://emercoin.com/en/documentation/blockchain-services/emerdns/emerdns-introduction/" target="_blank">EmerDNS</a> domain name. Please correct it or don\'t specify it.';
            page_end();
        }
        else {
            if (strpos($_POST['url'], $output) === false) {
                echo "This domain name is associated with a different IP address ($output). Please correct it.";
                page_end();
            }
       }
    }   
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
    //if ((inet_pton($ip) !== false) && true) {
    if ((filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== FALSE) && preg_match('/^0{0,1}[2-3][a-f0-9]{0,2}:/', $ip)) {
		$addr_OK = true;
	}
}
if ($addr_OK === false) {
	echo 'The address you have entered is incorrect!';
	page_end();
}

//include dir
list($scriptPath) = get_included_files();
$dir = dirname("$scriptPath");

//meshname
$meshname = '';
require_once("$dir/../php-backend/base32.php");
$parsed_url = parse_url($url);
if ($parsed_url) {
    $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $url_path = (isset($parsed_url['path']) && $parsed_url['path'] != '/') ? $parsed_url['path'] : '';
    $meshname = base32_encode(inet_pton(str_replace('[', '' , str_replace(']', '', $host)))) . '.meship' . "$port$url_path";
}

if ($dname == '' && $EmerDNS == '') {
    $url_p = preg_replace('{/$}', '', $url) . '%';
    $nrows = 0;
    try {
        $query = $db->prepare('SELECT ID FROM Sites WHERE URL LIKE :url LIMIT 1;');
        $query->bindParam(':url', $url_p);
        $query->execute();
        $nrows = count($query->fetchAll());
    } catch (PDOException $e) {
        echo 'Something went wrong. Please contact the site administrator.';
        echo '<br>' . $e->getMessage() . '<br>';
        page_end();
    }
    if ($nrows > 0) {
        echo 'The site is already contained in the catalog!';
        page_end();
    }
} elseif ($dname != '') { // $dname != ''
    try {
        $query = $db->prepare('SELECT ID FROM Sites WHERE ALFIS_DName = :dname LIMIT 1;');
        $query->bindParam(':dname', $dname);
        $query->execute();
        $nrows = count($query->fetchAll());
    } catch (PDOException $e) {
        echo 'Something went wrong. Please contact the site administrator.';
        echo '<br>' . $e->getMessage() . '<br>';
        page_end();
    }
    if ($nrows > 0) {
        echo 'The site is already contained in the catalog!';
        page_end();
    }
} elseif ($EmerDNS != '') { // $EmerDNS != ''
    try {
        $query = $db->prepare('SELECT ID FROM Sites WHERE EmerDNS = :emerdns LIMIT 1;');
        $query->bindParam(':emerdns', $EmerDNS);
        $query->execute();
        $nrows = count($query->fetchAll());
    } catch (PDOException $e) {
        echo 'Something went wrong. Please contact the site administrator.';
        echo '<br>' . $e->getMessage() . '<br>';
        page_end();
    }
    if ($nrows > 0) {
        echo 'The site is already contained in the catalog!';
        page_end();
    }
}

$dt = date("Y-m-d\ H:i:s");

//add site to DB
try {
    $query = $db->prepare('INSERT INTO Sites (URL, Description, Available, ALFIS_DName, EmerDNS, meshname, AvailabilityDate, NumberOfChecks, NumberOfUnavailability) VALUES (:url, :description, 1, :dname, :EmerDNS, :meshname, :date, 0, 0);');
    $query->bindValue(':url', $url, PDO::PARAM_STR);
    $query->bindValue(':description', $description, PDO::PARAM_STR);
    $query->bindValue(':dname', $dname, PDO::PARAM_STR);
    $query->bindValue(':EmerDNS', $EmerDNS, PDO::PARAM_STR);
    $query->bindValue(':meshname', $meshname, PDO::PARAM_STR);
    $query->bindValue(':date', $dt, PDO::PARAM_STR);
    $query->execute();
    //$query = $db->query('SELECT last_insert_rowid() AS SiteID;');
    //while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    //    $siteID = "{$row['SiteID']}"; break;
    //}
    $siteID = $db->lastInsertId();
} catch (PDOException $e) {
    echo 'Something went wrong. Please contact the site administrator.';
    echo '<br>' . $e->getMessage() . '<br>';
    page_end();
}

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
    $query_string = '';
    if ($categCount > 0) {
        try {
            $db->beginTransaction();
            $query = $db->prepare('INSERT INTO SitesCategories (Site, Category) VALUES (:SiteID, :CategoryID)');
            for($i = 0; $i < $categCount; $i++) {
                if (in_array($categ[$i], $categories_in_DB)) {
                    $query->bindValue(":SiteID", $siteID, PDO::PARAM_INT);
                    $query->bindValue(":CategoryID", $categ[$i], PDO::PARAM_INT);
                    $query->execute();
                }
            }
            $db->commit();
        } catch (PDOException $e) {
            $db->rollback();
            echo 'Something went wrong. Please contact the site administrator.';
            echo '<br>' . $e->getMessage() . '<br>';
            page_end();
        }
    }
}

$db = null;// close DB connection

echo "The site was successfully added! After a while, it will appear in the list.<br><a class=\"black\" href=\"/\">Return</a> to the main page.";

//regenerate HTML in background
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

// --- matrix notify
$scriptName = "$dir/../php-backend/ygg_matrix_notify.php";
if(file_exists("$scriptName")) {
    require_once("$scriptName");
    if (function_exists('mtrxNotify')) {
        mtrxNotify('A new site has been added:', $url, $description);
    }
}
// --- matrix notify

page_end();

?>
