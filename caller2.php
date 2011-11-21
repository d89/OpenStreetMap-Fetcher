<?php
header('content-type: text/html; charset=utf-8');
echo "<meta charset='utf8' />";
echo "<h1>Request</h1>";

$url = 'http://www.overpass-api.de/api/xapi?node[bbox=8.502,49.780,8.781,49.96]';
$params = isset($_GET['params']) ? htmlentities($_GET['params'], ENT_COMPAT, "UTF-8") : '[amenity=restaurant|fast_food]';
?>
<table>
    <tr>
        <td>
            <?php echo $url; ?>
        </td>
        <td>
            <form action="caller2.php" method="get">
            <input type="text" name="params" value="<?php echo $params; ?>" style="margin-top: 16px;width:300px" />
            <input type="submit" value="go" />
            </form>
        </td>
</table>
<?php

$url .= $params;

echo "<h1>Response</h1>";
echo "<b>" . $url . "</b>";
$result = file_get_contents($url);
echo "<pre>";

print_r(htmlentities($result, ENT_COMPAT, "UTF-8"));

echo "</pre>";



    
