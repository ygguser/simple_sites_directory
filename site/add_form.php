<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Adding a site to the directory</title>
<link rel="SHORTCUT ICON" href="favicon.ico">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="description" content="Web server addresses on the Yggdrasil network">
<style>
    input[type='text'], select {
        width: 450px;
    }
    BODY {
        font-family: sans-serif, Verdana, Arial, Helvetica;
    }
    A.black:link {
        color: #333;
        text-decoration: underline;
    }
    A.black:visited {
        color: #333;
        text-decoration: underline;
    }
    A.black:hover {
        color: #ff0000;
        text-decoration: underline;
    }
</style>
</head>
<body style="background-color: #f5f5f0;">

<?php
try {
    $db = new SQLite3('./../database.db', SQLITE3_OPEN_READONLY);
} catch (Exception $exception) { 
    die('Can\'t open database!</body></html>');
}
$result = $db->query('SELECT ID, Name FROM Categories ORDER BY Sorting');

$nrows = 0;//count of rows returned
while ($result->fetchArray())
    $nrows++;
$result->reset();
?>

<div class="page">
<h3>Adding a site to the directory</h3><br>
<form method="post" action="add.php">
<table>
<tr>
<td>URL:</td><td><input type="text" name="url" maxlength="500" placeholder="http://[xxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx]" value=""> <font color="red">*</font></td>
</tr>
<tr>
<td>Description:</td><td><input type="text" name="description" maxlength="500" placeholder="A brief description of the site" value=""> <font color="red">*</font></td>
</tr>
<tr>
<td>Domain name (<a class="black" href="http://[222:a8e4:50cd:55c:788e:b0a5:4e2f:a92c]/doku.php?id=yggdrasil:dns:alfis" target="_blank">ALFIS</a>):</td><td><input type="text" name="DomainName" maxlength="500" placeholder="Domain name (for example: sites.ygg)" value=""></td>
</tr>
<tr>
<td>Domain name (<a class="black" href="http://[222:a8e4:50cd:55c:788e:b0a5:4e2f:a92c]/doku.php?id=yggdrasil:dns:emerdns" target="_blank">EmerDNS</a>):</td><td><input type="text" name="EmerDNS" maxlength="500" placeholder="Domain name (for example: sites.y.lib)" value=""></td>
</tr>
<tr>
<td>Categories (<a class="black" href="javascript:void(0);" onclick='document.getElementById("select_categories").selectedIndex = -1;'>&#10007;</a>):</td>
<?php
echo "<td><select id=\"select_categories\" multiple name=\"categories[]\" size=\"" . $nrows . "\">";
while ($row = $result->fetchArray()) {
    echo '<option value="' . $row['ID'] . '">'. $row['Name'] . '</option>';
}
$db->close();
?>
</select></td>
</tr>
<tr>
<td><img src="captcha.php"></td><td><input type="text" name="code" maxlength="5" placeholder="verification code" value=""> <font color="red">*</font></td>
</tr>
<tr><td></td><td><input type="submit" name="submit" value="Add Site"></td></tr>
</table>
</form>
</div>
</body>
</html>
