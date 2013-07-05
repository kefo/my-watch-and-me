<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo $page_title ?></title>
		<meta charset="UTF-8">
		<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
		<?php if (isset($run["id"])) { ?>
			<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.css" />
			<!--[if lte IE 8]>
				<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.ie.css" />
			<![endif]-->
			<script src="http://cdn.leafletjs.com/leaflet-0.5.1/leaflet.js"></script>
			<script src="<?php echo RELATIVEPATH; ?>run/<?php echo $run["id"] ?>/feature.geojson"></script>
		<?php } else { ?>
			<link rel="stylesheet" href="<?php echo RELATIVEPATH; ?>static/css/blue/style.css" type="text/css" media="print, projection, screen" />
			<script type="text/javascript" src="<?php echo RELATIVEPATH; ?>static/js/jquery.tablesorter.js"></script> 
			
			<script type="text/javascript">
				$(document).ready(function() 
					{ 
						$("#runsTable").tablesorter({sortList: [[0,1]]}); 
					} 
				); 
			</script>
		<?php } ?>
		
			<link href="<?php echo RELATIVEPATH; ?>static/css/bootstrap.css" rel="stylesheet">
			<style>
				body {
					padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
				}
			</style>
		</head>

	<body>
    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="<?php echo RELATIVEPATH; ?>">Runs</a>
          
          
          <div class="nav-collapse collapse">
            <ul class="nav">
              <!-- <li class="active"><a href="runs">Runs</a></li> -->
              <!-- <li><a href="#about">About</a></li> -->           
            </ul>
          </div>
          <div class="nav-collapse collapse">
            <ul class="nav pull-right">
              <?php 
                if (isset($userdata['logged_in']) && $userdata['logged_in']) { 
                    echo '<li><a href="' . RELATIVEPATH . 'runs/unprocessed-runs">Unprocessed Runs</a></li>';
                    echo '<li><a href="' . RELATIVEPATH . 'runs/process-tcx">Process TCX files</a></li>';
                    echo '<li><a href="' . RELATIVEPATH . 'manage/logout">Logout (' . $userdata['email'] . ')</a></li>';                    
                } else {
                    echo '<li><a href="' . RELATIVEPATH . 'manage/login">Login</a></li>';
                }
              ?>              
            </ul>
          </div>
          
        </div>
      </div>
    </div>

    <div class="container">
