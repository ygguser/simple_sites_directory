<?PHP

list($scriptPath) = get_included_files();
$dir = dirname("$scriptPath");

$web_root = "$dir/../site/";
$path_to_db = "$dir/../database.db";

$tmpdir = '/tmp/YggSites/html';
if (!is_dir($tmpdir)) {
    $oldmask = umask(0);
    if (!mkdir($tmpdir, 0777, true)) {
        umask($oldmask);
        die('Failed to create a temporary directory. Exit.');
    }
    umask($oldmask);
} else {
    if (file_exists("$tmpdir/index.html")) 
        die('Another process is running. Exit.');
}

//////////////////////////////////////////////////// get data

try {
    $db = new SQLite3("$path_to_db", SQLITE3_OPEN_READONLY);
} catch (Exception $exception) {
    die('Can\'t open database. Exit.');
}

//for data for index.html

$query = 'SELECT *, round(CASE WHEN NumberOfUnavailability = 0 THEN 100.0 ELSE CAST(NumberOfChecks - NumberOfUnavailability AS REAL) / CAST(NumberOfChecks AS REAL) * CAST(100 AS REAL) END, 2) AS Uptime FROM Sites;';

$result_sites = $db->query("$query");

$nrows = 0;//count of rows returned
while ($result_sites->fetchArray())
    $nrows++;
$result_sites->reset();

// data for categories.html
// creating the necessary temporary tables
$query = <<<EOL
PRAGMA temp_store=2;
--DROP TABLE IF EXISTS tmp_SitesCountInCategory; DROP TABLE IF EXISTS tmp_SitesWithCategories;

CREATE TEMPORARY TABLE tmp_SitesWithCategories AS
SELECT Sites.*, round(CASE WHEN Sites.NumberOfUnavailability = 0 THEN 100.0 ELSE CAST(Sites.NumberOfChecks - Sites.NumberOfUnavailability AS REAL) / CAST(Sites.NumberOfChecks AS REAL) * CAST(100 AS REAL) END, 2) AS Uptime, SitesCategories.Category AS CategoryID, Categories.Name AS CategoryName, Categories.Sorting AS CategorySorting
FROM Sites 
LEFT JOIN SitesCategories ON Sites.ID = SitesCategories.Site 
LEFT JOIN Categories ON SitesCategories.Category = Categories.ID;

CREATE TEMPORARY TABLE tmp_SitesCountInCategory AS
SELECT count(Sites.ID) AS SitesCountInCategory, SitesCategories.Category AS CategoryID
FROM Sites LEFT JOIN SitesCategories ON Sites.ID = SitesCategories.Site GROUP BY SitesCategories.Category;
EOL;
$db->query("$query");

// Requesting data
$query = <<<EOL
SELECT tmp_SitesWithCategories.*, tmp_SitesCountInCategory.SitesCountInCategory
from tmp_SitesWithCategories LEFT JOIN tmp_SitesCountInCategory ON ifnull(tmp_SitesWithCategories.CategoryID,-1) = ifnull(tmp_SitesCountInCategory.CategoryID,-1)
ORDER BY ifnull(tmp_SitesWithCategories.CategorySorting, 9999), tmp_SitesWithCategories.CategoryID, tmp_SitesWithCategories.ID;
EOL;

$result_sites_with_categories = $db->query("$query");

//////////////////////////////////////////////////// generate index.html

echo "Generating index.html...\n";
$index_html = '';

$page_top = <<<EOL
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Web-sites directory</title>
<link rel="stylesheet" href="css/page_table.css">
<link rel="SHORTCUT ICON" href="favicon.ico">
<link rel="apple-touch-icon" href="favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="description" content="Web server addresses on the Yggdrasil network">

<script src="js/jquery-1.7.1.min.js"></script>
<link rel="stylesheet" href="css/m_jquery.mobile-1.4.5.css">
<script type="text/javascript">
$(document).bind("mobileinit", function () {
    $.mobile.ajaxEnabled = false;
});
</script>
<script src="js/jquery.mobile-1.4.5.min.js"></script>

</head>
<body>
<!--<img width="400px" src="pic/new_year.png" style="position: absolute; z-index: 1; top: 0px; left: 0px;">
<div style="height: 30px; background: url(pic/garland.gif) repeat-x 100%;"></div>//-->
<div class="page"><h1>Yggdrasil Web directory</h1>
EOL;
$page_middle = <<<EOL
<thead><tr><th data-priority="critical">Address</th><th data-priority="critical">Description</th><th data-priority="critical">Domain name (<a target="_blank" href="http://[300:529f:150c:eafe::6]/doku.php?id=en:yggdrasil:wyrd_register" class="black u" >Wyrd</a>)</th><th data-priority="critical">Domain name (<a target="_blank" href="http://[300:529f:150c:eafe::6]/doku.php?id=yggdrasil:dns:alfis" class="black u" >ALFIS</a>)</th><th data-priority="1">Was online</th><th data-priority="1">Uptime</th></tr></thead>
<tbody>
EOL;
$page_bottom = <<<EOL
<font size="-2"><br>If someone is interested in how it works, take a look here: <a class="black u" href="https://notabug.org/ygguser/simple_sites_directory" target="_blank">NotABug repo</a>.<br></font><br>
<a class="black" href="https://matrix.to/#/@0n0n:matrix.org">@0n0n:matrix.org</a>
<br>&nbsp;</div><!--//page//-->
</body>
</html>
EOL;

# the beginning of the file
$index_html = $page_top;

// date and number of rows
$objDateTime = new DateTime('NOW');
$time_updated = $objDateTime->format('Y-m-d H:i:sP');

$index_html .= <<<EOL
updated: $time_updated; number of rows: <b>$nrows</b><br><font size="-1">(strikethrough lines are sites inaccessible at the time of the last availability check; sites unavailable for more than a month will be deleted)</font><br><br>If your site is not in this list, you can <a class="black u" href="/add_form.php">add it manually</a>.<br><font size="-1">You can also <a class="black u" href="/change_form.php">change</a> the description and categories of existing entries in the list.</font><br><br><center><b>Simple list │ <a class="black u" href="categories.html">Categories</a></b></center>
EOL;

// table header
$index_html .= '<table data-role="table" data-mode="columntoggle" clsass="ui-responsive" id="maintable" cellspacing="0">';
$index_html .= $page_middle;

// content of the table
while ($row = $result_sites->fetchArray()) {
    $ClassAvUnav = $row['Available'] == 1 ? 'n0rma1' : 'unavai1ab1e';
    $URL = "{$row['URL']}";
    $URL_pieces = explode(":", $URL);
    $Protocol = $URL_pieces[0];
    $OnlineAt = $row['Available'] == 1 ? '' : "{$row['AvailabilityDate']}";
    $WDName = "{$row['Wyrd_DName']}";
    $DName_W_text = $WDName == '' ? '' : "<div class=\"$ClassAvUnav\"><a class=\"black\" target=\"_blank\" href=\"$Protocol://$WDName/\">$WDName</a></div>";
    $ADName = "{$row['ALFIS_DName']}";
    $DName_A_text = $ADName == '' ? '' : "<div class=\"$ClassAvUnav\"><a class=\"black\" target=\"_blank\" href=\"$Protocol://$ADName/\">$ADName</a></div>";
    $Uptime = number_format($row['Uptime'], 2, '.', '');
    $index_html .= <<<EOL
    <tr><td class="addr"><div class="$ClassAvUnav"><a class="black" target="_blank" href="$URL">$URL</a></div></td><td class="descr"><div class="$ClassAvUnav">{$row['Description']}</div></td><td class="dname">$DName_W_text</td><td class="dname">$DName_A_text</td><td class="OnlineAt">$OnlineAt</td><td class="uptime">$Uptime</td></tr>
EOL;
}

// the end of the table
$index_html .= '</tbody></table>';

// the end of the html-page
$index_html .= $page_bottom;

// save index.html
if (file_exists("$tmpdir/index.html")) 
    die('Another process is running. Exit.');

$index_html = str_replace(array("\n", "\r"), '', $index_html);
$fp = fopen("$tmpdir/index.html", 'w');
if ($fp) {
    fwrite($fp, "$index_html");
    fclose($fp);
}

unset($result_sites);
unset($index_html);

//////////////////////////////////////////////////// generate categories.html

$categories_html = '';
echo "Generating categories.html...\n";

// the beginning of the file
$categories_html = $page_top;

// date and number of rows
$categories_html .= <<<EOL
updated: $time_updated number of sites: <b>$nrows</b><br><font size="-1">(strikethrough lines are sites inaccessible at the time of the last availability check; sites unavailable for more than a month will be deleted)</font><br><br>If your site is not in these lists, you can <a class="black" href="/add_form.php"><u>add it manually</u></a>.<br><font size="-1">You can also <a class="black" href="/change_form.php"><u>change</u></a> the description and categories of existing entries in the list.</font><br><br><b><a class="black" href="/"><u>Simple list</u></a> │ Categories</b><br><br>
EOL;

$categories_html .= '<div style="line-height: 130%;">';

// content of the table
$prev_category_ID = '';
while ($row = $result_sites_with_categories->fetchArray()) {
    $ClassAvUnav = $row['Available'] == 1 ? 'n0rma1' : 'unavai1ab1e';
    $URL = "{$row['URL']}";
    $URL_pieces = explode(":", $URL);
    $Protocol = $URL_pieces[0];
    $OnlineAt = $row['Available'] == 1 ? '' : "{$row['AvailabilityDate']}";
    $WDName = "{$row['Wyrd_DName']}";
    $DName_W_text = $WDName == '' ? '' : "<div class=\"$ClassAvUnav\"><a class=\"black\" target=\"_blank\" href=\"$Protocol://$WDName/\">$WDName</a></div>";
    $ADName = "{$row['ALFIS_DName']}";
    $DName_A_text = $ADName == '' ? '' : "<div class=\"$ClassAvUnav\"><a class=\"black\" target=\"_blank\" href=\"$Protocol://$ADName/\">$ADName</a></div>";
    $Uptime = number_format($row['Uptime'], 2, '.', '');

    $entire_table_row = "<tr><td class=\"addr\"><div class=\"$ClassAvUnav\"><a class=\"black\" target=\"_blank\" href=\"$URL\">$URL</a></div></td><td class=\"descr\"><div class=\"$ClassAvUnav\">{$row['Description']}</div></td><td class=\"dname\">$DName_W_text</td><td class=\"dname\">$DName_A_text</td><td class=\"OnlineAt\">$OnlineAt</td><td class=\"uptime\">$Uptime</td></tr>";

    $category_ID = "{$row['CategoryID']}";
    $category_name = "{$row['CategoryName']}";
    $category_name =  $category_name == '' ? 'Unsorted' : $category_name;

    if ($prev_category_ID != $category_ID ) {
        if ($prev_category_ID != '') {
            //the end of prev table
            $categories_html .= '</tbody></table></ul></div>';
        }
    
        //button    
        $categories_html .= '<div data-role="collapsible" data-content-theme="false" data-mini="true" data-collapsed-icon="" data-expanded-icon="">';
        $categories_html .= "<h3>$category_name ({$row['SitesCountInCategory']})</h3>";
        $categories_html .= '<ul data-role="listview">';

        //table header
        $categories_html .= <<<EOL
        <table data-role="table" data-mode="columntoggle" clsass="ui-responsive" id="table$category_ID" cellspacing="0"> 
EOL;
        $categories_html .= $page_middle;

        //table row
        $categories_html .= $entire_table_row;
        $prev_category_ID = $category_ID;
    } else {
        //table row
        $categories_html .= $entire_table_row;
    }
}

//the end of the last table
$categories_html .= '</tbody></table></ul></div>';
$categories_html .= '</div>';// line-height
$categories_html .=  $page_bottom; 

$db->close();

// save categories.html
$categories_html = str_replace(array("\n", "\r"), '', $categories_html);
$fp = fopen("$tmpdir/categories.html", 'w');
if ($fp) {
    fwrite($fp, "$categories_html");
    fclose($fp);
}

unset($result_sites_with_categories);
unset($categories_html);

//////////////////////////////////////////////////// make archive

echo "Making archives...\n";
$cmd = <<<EOL
find "$tmpdir/" -type f -name "*.html" -exec gzip --force --keep {} \; >/dev/null 2>&1 
EOL;

exec("$cmd"); 
//////////////////////////////////////////////////// copy HTML-pages

$cmd = <<<EOL
find "$tmpdir/" -maxdepth 1 -type f \( -name '*.html' -o -name '*.gz' \) -exec cp {} "$web_root" \; >/dev/null 2>&1 
EOL;

exec("$cmd");
//////////////////////////////////////////////////// remove tmp-files
 
$cmd = <<<EOL
find "$tmpdir/" -maxdepth 1 -type f \( -name '*.html' -o -name '*.gz' \) -exec rm -f {} \; >/dev/null 2>&1 
EOL;

exec("$cmd");

?>
