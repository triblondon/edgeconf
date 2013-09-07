<html>
<head>
	<link href='http://fonts.googleapis.com/css?family=Lato:300,400,700,900' rel='stylesheet' type='text/css'>
  	<style>
	:root {-webkit-print-color-adjust:exact}
	* { box-sizing: border-box; }
	body, html { height: 100%; width: 100%; margin:0; padding:0; }
	body { font-family: 'Lato', sans-serif; background: #f3f3f3; }
	section { display: table; width: 850px; margin: 0 auto; background:white; height: 138mm; border-bottom: 1px dotted #ddd;}
	section:nth-child(2n) { border-bottom: 1px dashed #ccc; }
	section > div { display: table-cell; width: 50%; text-align: center; padding-top: 30mm; position: relative; border-left: 1px dotted #ddd; overflow:hidden; }
	h1 { font-size: 60px; font-weight: normal; margin: 0; position: absolute; width: 100%; top: 30mm; }
	h2 { margin-top:0; color: #555; font-weight: normal; font-size: 30px;position: absolute; width: 100%; top: 49mm;}
	h3 { margin-top: 20mm; position: absolute; top: 50mm; width: 100%;}
	.footer { position: absolute; left:0; right:0; bottom:0; height: 170px; background: url(http://www.edgeconf.com/2013-nyc/images/bg_billboard.jpg); background-size: cover;}
	.detail { list-style-type: none; position: absolute; bottom: 60px; left: 20px; padding:0; margin: 0; text-align: left; color: white; }

	.logo { width: 140px; position: absolute; bottom: 35px; right: 25px;}
	.bg { position: absolute; z-index: 0; top: 0; left: -27px; width: 479px; height: 546px; }

	@media print {
		@page { size: 210mm 297mm; margin: 0; }
		section { border-bottom: none; width: 100%;}
		section > div { border-left: none; }
		section:nth-child(2n) { page-break-after: always; border-bottom: none; }
		body { background-color: white; }
	}
	</style>
</head>
<body>
<?php

require_once '../../app/global';

$eb_client = new Eventbrite(array('app_key'=>EVENTBRITE_APPKEY, 'user_key'=>EVENTBRITE_USERKEY));

$sides = array('left', 'right');
$list = $eb_client->event_list_attendees(array('id'=>EVENTBRITE_EID));
foreach ($list->attendees as $rec) {
	$company = isset($rec->attendee->company) ? $rec->attendee->company : '';
	if (!$rec->attendee->first_name) continue;
	?>
	<section>
		<?php
		foreach ($sides as $side) {
			?>
			<div class='<?php echo $side ?>'>
				<h1><?php echo ucfirst($rec->attendee->first_name); ?></h1>
				<h2><?php echo ucfirst($rec->attendee->last_name); ?></h2>
				<h3><?php echo $company?></h3>
				<div class='footer'>
					<ul class='detail'>
						<li>Edge conference</li>
						<li>23 September 2013</li>
						<li>Google</li>
					</ul>
					<img class='logo' src='http://www.edgeconf.com/2013-nyc/images/logo.png' />
				</div>
			</div>
			<?php
		}
		?>
	</section>
	<?php
}
?>
</body>
</html>
