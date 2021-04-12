<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Changing data in the site catalog</title>
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
        color: #000000;
    }
    A.black:visited {
        color: #000000;
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
<h3>Changing data in the site directory</h3>
<p>We need to make sure that you own the service whose data you want to change.<br>
To do this, you need to create a file with this name in the site's directory:<br>
<?php
$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
$rndfname = str_shuffle($permitted_chars) . '.html';
session_start();
$_SESSION['rndfname'] = $rndfname;
echo "$rndfname";
?>
</p>
<p>You can do this by going to the site's directory and executing this command:<br>
<br><font color="red"><code>touch <?php echo "$rndfname"?></code></font></p>
<p>Then, without reloading this page, you need to fill out and submit this form.</p>
<form method="post" action="change.php">
<table>
<tr>
<td>URL:</td><td><input type="text" name="url" maxlength="500" placeholder="http://[xxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx]" value=""> <font color="red">*</font></td>
</tr>
<tr>
<td>Description:</td><td><input type="text" name="description" maxlength="500" placeholder="A brief description of the site" value=""> <font color="red">*</font></td>
</tr>
<tr>
<td>Domain name (<a class="black" href="http://[300:529f:150c:eafe::6]/doku.php?id=yggdrasil:dns:alfis" target="_blank">ALFIS</a>):</td><td><input type="text" name="domain" maxlength="500" placeholder="example.ygg" value=""></td>
</tr>
<tr>
<td>Categories:</td>
<?php
echo '<td><select multiple name="categories[]" size="' . $nrows . '">';
while ($row = $result->fetchArray()) {
        echo '<option value="' . $row['ID'] . '">'. $row['Name'] . '</option>';
}
?>
</select></td>
</tr>
<tr>
<td><td><input type="checkbox" name="deletesite"> Delete this site from the list.</td>
</tr>
<tr>
<td><img src="captcha.php"></td><td><input type="text" name="code" maxlength="5" placeholder="verification code" value=""> <font color="red">*</font></td>
</tr>
<tr><td></td><td><input type="submit" name="submit" value="Change data"></td></tr>
</table>
</form>
</div>
</body>
</html>
