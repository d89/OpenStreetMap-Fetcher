<?php
header('content-type: text/html; charset=utf-8');
require_once "config.php";

$db = DB::get();

if (!empty($_GET['detail']))
{
    $stmnt = $db->prepare('SELECT message FROM `history` WHERE requestkey = ?');
    $stmnt->execute(array($_GET['detail']));
    $res = $stmnt->fetchAll(PDO::FETCH_ASSOC);
    die(nl2br(htmlspecialchars($res[0]['message'], ENT_COMPAT, "UTF-8")));
}

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
        $("a.showtext").click(function()
        {
            var id =  $(this).attr("id"),
                textelem = $('#longtext' + id),
                link = $(this);
            
            if (textelem.is(":visible"))
            {
                textelem.html("").hide();
                link.text("Inhalt anzeigen");
            }
            else
            {
                link.text("Inhalt lädt....");
                
                $.get('stalker.php?detail=' + id, function(data) {
                    textelem.show().html(data);
                    link.text("Inhalt ausblenden");
                });
            }
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
while ($row = $stmnt->fetch(PDO::FETCH_ASSOC))
{
    ?>
    <tr>
        <td><?php echo $row['time']; ?></td>
        <td><?php echo $row['userid']; ?></td>
        <td><?php echo $row['action']; ?></td>
        <td><?php echo '<a href="http://maps.google.com/maps?q=@(' . $row['lat'] . ',' . $row['long'] . ')">' . $row['lat'] . " / " . $row['long']; ?></a></td>
        <td><?php echo $row['totaltime']; ?></td>
        <td><?php echo $row['successfull']; ?></td>
        <td>
             <a id="<?php echo $row['requestkey']; ?>" class="showtext">Inhalt anzeigen</a>
             <div style="display:none" id="longtext<?php echo $row['requestkey']; ?>"></div>
        </td>
    </tr>
    <?php
}
        
?>
</table>