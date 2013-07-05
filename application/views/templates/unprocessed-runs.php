        <script>
            function loadrun(rowid){
                var rowid = rowid;
                $('#'+rowid + ' td').css('background-color', '#fcf8e3');               
                $.ajax({
                    type: "GET",
                    url: "<?php echo RELATIVEPATH;?>run/" + rowid + "/load",
                    success: function(html){
                        //$('#'+rowid + ' tr').addClass("alert alert-success");
                        $('#'+rowid + ' td').css('background-color', '#dff0d8');
                        setTimeout(
                            function() 
                            {
                                $('#'+rowid).remove();
                            }, 2000);
                    }
                });                 
            }
        </script>
        <h1>Runs</h1>
        <table cellpadding="0" cellspacing="0" border="0" class="tablesorter table table-striped table-bordered" id="runsTable">
            <thead> 
                <tr> 
                    <th>ID</th>
                    <th>Filesize</th>
                    <th>Filename</th>
                    <th> </th>
		</tr>
            <thead> 
            <tbody> 
            <?php 
                foreach ($xmlfiles as $r) {
                    echo "<tr id=\"" . $r["id"] . "\">\n";
                    echo "\t<td>" . $r["id"] . "</td>\n";
                    echo "\t<td>" . $r["filesize"] . "</td>\n";
                    echo "\t<td>" . $r["filename"] . "</td>\n";
                    //echo "\t<td><a href=\"" . RELATIVEPATH . "run/" . $r["id"] . "/load\">Load</a></td>\n";
                    echo "\t<td><a href=\"javascript:void(0);\" onclick=\"javascript:loadrun('" . $r["id"] . "');\">Load</a></td>\n";
                    echo "</tr>\n";
		}
            ?>
            </tbody>
	</table>
