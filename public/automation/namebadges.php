<html>
<head>
	<link href='http://fonts.googleapis.com/css?family=Lato:300,400,700,900' rel='stylesheet' type='text/css'>
	<link href='//cdnjs.cloudflare.com/ajax/libs/font-awesome/3.2.1/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
  	<style>
	:root {-webkit-print-color-adjust:exact}
  * { box-sizing: border-box; }
  body, html { height: 100%; width: 100%; margin:0; padding:0; }
  body { font-family: 'Lato', sans-serif; background: #f3f3f3; }
  section { display: table; width: 850px; margin: 0 auto; background:white; height: 131mm; border-bottom: 1px dotted #ddd;}
  section > div { display: table-cell; width: 50%; text-align: center; padding-top: 30mm; position: relative; border-left: 1px dotted #ddd; overflow:hidden; }
  h1 { font-size: 60px; font-weight: normal; margin: 0; position: absolute; width: 100%; top: 23mm; }
  h2 { margin-top:0; color: #555; font-weight: normal; font-size: 30px;position: absolute; width: 100%; top: 42mm;}
  h3 { margin-top: 20mm; position: absolute; top: 43mm; width: 100%;}
  .footer { position: absolute; left:0; right:0; bottom:30px; height: 170px; background: url(http://www.edgeconf.com/2013-nyc/images/bg_billboard.jpg); background-size: cover;}
  .detail { list-style-type: none; position: absolute; bottom: 60px; left: 20px; padding:0; margin: 0; text-align: left; color: white; }
  .interests { list-style-type: none; position: absolute; bottom: 0; left:0; right: 0; height: 30px; padding:0; margin:0; background:black; display: flex; }
  .interests li { flex: 1 1 auto; padding-top: 4px; }
  .interests li i { font-family: FontAwesome; color: white; font-size: 21px; font-style: normal; font-weight: normal; -webkit-font-smoothing: antialiased }
  .interest-rendering-performance { background: #15B100; }
  .interest-rendering-performance i:before { content:'\F085'; }
  .interest-payments { background: #088; }
  .interest-payments i:before { content: '\F155'; }
  .interest-offline { background: orange; }
  .interest-offline i:before { content: '\F011'; }
  .interest-real-time-data { background: #00d;}
  .interest-real-time-data i:before { content: '\F0E7'; }
  .interest-responsive-images { background: #1AE3FF; }
  .interest-responsive-images i:before { content: '\F03E'; }
  .interest-legacy-clients { background: red; }
  .interest-legacy-clients i:before { content:'\F188'; }
  .interest-third-party-apps, .interest-third-party-scripts { background: purple;}
  .interest-third-party-apps i:before, .interest-third-party-scripts i:before { content:'\F121';}


  .logo { width: 140px; position: absolute; bottom: 35px; right: 25px;}
  .bg { position: absolute; z-index: 0; top: 0; left: -27px; width: 479px; height: 546px; }

  @media print {
    @page { margin: 0; }
    section { border-bottom: none; width: 100%;}
    section > div { border-left: none; }
    section:nth-child(2n) { page-break-after: always; }
    body { background-color: white; }
  }
	</style>
</head>
<body>
<?php

require_once '../../app/global';


/* Get Google docs data for session lookups */

$sessioninterest = array();
$http = new HTTPRequest(GSHEET_FORM);
$resp = $http->send();
file_put_contents('/tmp/edgecsv2', $resp->getBody());
$csv = new Coseva\CSV('/tmp/edgecsv2');
$csv->parse();
foreach ($csv as $row) {
	if ($row[0] == 'll' or empty($row[1]) or $row[1] == 'VOID') continue;
	$sessioninterest[strtolower(trim($row[3]))] = explode(',', $row[5]);
}


/* Get eventbrite data for badges */

$eb_client = new Eventbrite(array('app_key'=>EVENTBRITE_APPKEY, 'user_key'=>EVENTBRITE_USERKEY));

$sides = array('left', 'right');
$list = $eb_client->event_list_attendees(array('id'=>EVENTBRITE_EID));
$count = 0;
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
				<ul class='interests'>
				<?php
					if (isset($sessioninterest[$rec->attendee->email])) {
						foreach ($sessioninterest[$rec->attendee->email] as $sess) {
							$key = strtolower(str_replace(' ', '-', trim($sess)));
							?>
							<li class='interest-<?php echo $key; ?>'><i></i></li>
							<?php
						}
					}
				?>
				</ul>
			</div>
			<?php
		}
		?>
	</section>
	<?php
	//if ($count++ > 5) break;
}
?>
</body>
</html>
