<?php
header('content-type: text/html; charset=utf-8');
require_once "config.php";

$db = DB::get();
$stmnt = $db->prepare('SELECT * FROM `history` ORDER BY `time` DESC');
$stmnt->execute();
?>

<style>
    table {
        border-collapse: collapse;
    }
    
    td {
      vertical-align: top;
      padding: 5px;
      border: 2px solid #AAA;
      border-spacing: 0;
    }
    
    .toolong {
        display: none;
    }
    
    a {
        color: blue;
        cursor: pointer;
    }
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script>
    $(document).ready(function()
    {
        $("a").click(function()
        {
            var el = $("#longid" + $(this).attr("id"));
            el.slideToggle();
        });
    });
</script>
<h1>Stalkerpanel</h1>
<table>
    <tr>
        <th>from</th>
        <th>userid</th>
        <th>action</th>
        <th>koords</th>
        <th>time</th>
        <th>successfull</th>
        <th>message</th>
    </tr>
<?php
$id = 0;

while ($row = $stmnt->fetch(PDO::FETCH_ASSOC))
{
    $id++;
    
    ?>
    <tr>
        <td><?php echo $row['time']; ?></td>
        <td><?php echo $row['userid']; ?></td>
        <td><?php echo $row['action']; ?></td>
        <td><?php echo '<a href="http://maps.google.com/maps?q=@(' . $row['lat'] . ',' . $row['long'] . ')">' . $row['lat'] . " / " . $row['long']; ?></a></td>
        <td><?php echo $row['totaltime']; ?></td>
        <td><?php echo $row['successfull']; ?></td>
        <td>
            <?php 
            if (strlen($row['message']) > 400)
            {
            ?>
                <a id="<?php echo $id; ?>" class="showtext"><?php echo strlen($row['message']); ?> Zeichen anzeigen</a>
                <div id="longid<?php echo $id; ?>" class="toolong">
                    <?php echo nl2br($row['message']); ?>
                </div>
            <?php
            }
            else
                echo $row['message']; 
            ?>
        </td>
    </tr>
    <?php
}
        
?>
</table>