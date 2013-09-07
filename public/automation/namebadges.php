<html>
<head>
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700,600' rel='stylesheet' type='text/css'>
	<style>
	:root {-webkit-print-color-adjust:exact}
	* { box-sizing: border-box; }
	body, html { height: 100%; width: 100%; margin:0; padding:0; }
	body { font-family: 'Open Sans', sans-serif; background: #f3f3f3; }
	section { display: table; width: 850px; margin: 0 auto; background:white; height: 138mm; border-bottom: 1px dotted #ddd;}
	section:nth-child(2n) { border-bottom: 1px dashed #ccc; }
	section > div { display: table-cell; width: 50%; text-align: center; padding-top: 30mm; position: relative; border-left: 1px dotted #ddd; overflow:hidden; }
	h1 { font-size: 60px; font-weight: normal; margin: 0; position: absolute; width: 100%; top: 30mm; }
	h2 { margin-top:0; color: #555; font-weight: normal; font-size: 30px;position: absolute; width: 100%; top: 49mm;}
	h3 { margin-top: 20mm; position: absolute; top: 57mm; width: 100%;}

	.logo { width: 140px; height: 62px; position: absolute; bottom: 25px; right: 25px;}
	.bg { position: absolute; z-index: 0; top: 0; left: -27px; width: 479px; height: 546px; }

	@media print {
		@page { size: 210mm 297mm; margin: 0; }
		section { border-bottom: none; width: 100%;}
		section > div { border-left: none; }
		section:nth-child(2n) { page-break-after: always; border-bottom: none; }
	}
	</style>
</head>
<body>
<?php

require_once '../app/libraries/http/HTTPRequest';
require_once '../app/libraries/mysql/connection';
require_once '../app/libraries/eventbrite/eventbrite.php';
/*
$eb_client = new Eventbrite(array('app_key'=>'N43KISGOIHOYSEYUDN', 'user_key'=>'133354408930710386593'));

$list = $eb_client->event_list_attendees(array('id'=>4730469963));
foreach ($list->attendees as $rec) {
	$company = isset($rec->attendee->company) ? $rec->attendee->company : '';
	if (!$rec->attendee->first_name) continue;
	if (strtotime($rec->attendee->created) < 1359244800) continue;
	?>
	<section>
		<div class='left'>
			<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
			<h1><?php echo $rec->attendee->first_name?></h1>
			<h2><?php echo $rec->attendee->last_name?></h2>
			<h3><?php echo $company?></h3>
			<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
		</div>
		<div class='right'>
			<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
			<h1><?php echo $rec->attendee->first_name?></h1>
			<h2><?php echo $rec->attendee->last_name?></h2>
			<h3><?php echo $company?></h3>
			<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
		</div>
	</section>
	<?php
}
*/
?>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<h1>Shadow</h1>
		<h2>(I'm a girl)</h2>
		<h3>FT Labs</h3>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<h1>Shadow</h1>
		<h2>(I'm a girl)</h2>
		<h3>FT Labs</h3>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
<section>
	<div class='left'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
	<div class='right'>
		<img src="http://edgeconf.com/img/herobg3.jpg" class="bg"/>
		<img class='logo' src='http://edgeconf.com/img/logo_small.png' />
	</div>
</section>
</body>
</html>

















