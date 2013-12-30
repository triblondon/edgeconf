<?php

require_once "../../app/global";

session_start();
$auth = new GoogleAuth($_SESSION, '/oauth2callback.php');

$db = new MySQLConnection('localhost', MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME);
$eventid = 3;

$user = $auth->auth(false);
$saved = false;
$data = array();
$sessions = $db->queryLookupTable('SELECT id as k, name as v FROM sessions WHERE eventid=%d ORDER BY starttime', $eventid);
$countries = $db->queryLookupTable('SELECT iso as k, name as v FROM countries ORDER BY name');

// If submitting the form, save the data
if (!empty($user) and isset($_POST['givenname'])) {
	$data = array_merge($_POST, array('email'=>$user['email'], 'eventid'=>$eventid));

	// Nothing on attendance record can be modified, so just ignore it if it's already there
	$db->query('INSERT IGNORE INTO attendance SET {email}, {eventid}, datecreated=NOW()', $data);

	// Insert a new person record or update the details if already there
	$db->query('INSERT INTO people SET {email}, {givenname}, {familyname}, {travelorigin}, {org}, datecreated=NOW() ON DUPLICATE KEY UPDATE {givenname}, {familyname}, {travelorigin}, {org}', $data);

	// Save session participation proposals
	foreach ($data['sessions'] as $session) {
		$pdata = array_merge($data, array(
			"sessionid" => $session,
			"proposal" => $data['proposal_'.$session],
			"role" => "Delegate"
		));
		$db->query('INSERT INTO participation SET {email}, {sessionid}, {proposal}, {role}, datecreated=NOW() ON DUPLICATE KEY UPDATE {proposal}', $pdata);
	}
	$saved = true;

// Otherwise, if user is known, load details so they can modify existing records
} elseif (!empty($user)) {
	$data = $db->queryRow('SELECT * FROM people WHERE email=%s', $user['email']);
	$data = array_merge($data, $db->queryRow('SELECT * FROM attendance WHERE email=%s AND eventid=%d', $user['email'], EVENT_ID));
	$data['sessions'] = array();
	$proposals = $db->query('SELECT * FROM participation WHERE email=%s AND sessionid IN %d|list', $user['email'], array_keys($sessions));
	foreach ($proposals as $proposal) {
		$data['proposal_'.$proposal['sessionid']] = $proposal['proposal'];
		$data['sessions'][] = $proposal['sessionid'];
	}
}

header('Cache-Control: max-age=0, no-store, must-revalidate');

?>
<!DOCTYPE html>
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width" />
  <title>Register | Edge Conference</title>

  <link rel="stylesheet" href="stylesheets/normalize.css" />
  <link rel="stylesheet" href="stylesheets/app.css" />
  <link href='http://fonts.googleapis.com/css?family=Lato:300,400,700,900' rel='stylesheet' type='text/css'>

  <script src="javascripts/vendor/custom.modernizr.js"></script>

  <style>
  	form.register {
  		margin: 0;
  		padding: 0;
  	}
  	form.register input, form.register select {
  		margin: 0;
  	}
  	form.register .form-field {
  		margin-bottom: 25px;
  	}
  	form.register .form-field label:not(.inline) {
  	  font-size: 18px;
  	  margin: 0.1em 0 0.5em 0;
  	  color: black;
  	}
  	form.register .form-field .note {
  		margin: 0.5em 0;
  		color: #333;
  		font-size: 13px;
  		display: block;
  		line-height: normal;
  	}
  	form.register .form-field .error {
  		margin-top: 0.5em;
		color: red;
	}
  	form.register .form-field .error:before {
		content: '▲';
		margin-right: 5px;
	}
 	form.register .form-field label.sublabel {
  		font-size: 13px;
  		color: #555;
  		font-weight: bold;
  	}
  	form.register .form-field label.inline {
  	  margin: 0;
  	  padding: 0.3em 0;
  	  display: inline-block;
  	  min-width:49%;
  	}
  	form.register .form-field label.inline input {
  	  margin: 0 10px 0 0;
  	}
  	form.register .button {
		background: #333;
		border: none;
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		-ms-border-radius: 4px;
		-o-border-radius: 4px;
		border-radius: 4px;
		box-shadow: none;
		color: #FFF;
		text-transform: uppercase;
	}
  	form.register .button:hover {
		background: yellow;
		color: black;
	}
	form.register .proposal {
		display:none;
	}
	form.register #noproposals {
		font-style: italic;
	}
	form.register .form-field.disabled {
		opacity: 0.5;
		pointer-events: none;
	}
	.alert {
		border-radius: 4px;
		background: #eee;
		padding: 10px;
	}
	.alert strong {
		display: block;
	}
	textarea {
		resize: vertical;
	}
  </style>
</head>
<body>

	<header>
		<nav class="row">
			<ul class="small-10 columns small-centered">
				<li><a href="index.html">Overview</a></li>
				<li><a href="schedule.html">Schedule</a></li>
				<li><a href="faq.html">FAQs</a></li>
				<li><a href="#" style='color: #bbb; font-style: italic; font-size: 90%; text-transform: none' class="conf-toggle">Edge London <i class="icon-caret-down"></i></a>
		          <ul class="conf-list">
		            <li><a href="/2013-nyc">Edge NYC</a></li>
		            <li><a href="/2013-london">Edge London 2013</a></li>
		          </ul>
		        </li>
			</ul>
			<i class="icon-reorder"></i>
		</nav>
	</header>

	<div id="billboard" class="inner">
		<div class="row">
			<div class="small-12 large-2 columns">
				<h1 class="hide-text">Edge Conference</h1>
			</div>
			<div class="small-12 large-10 columns">
				<h2>Register</h2>
			</div>
		</div>
	</div>

	<?php
	if ($saved) {
		?>
		<section class="layer">
			<div class="row">
				<div class="small-12 columns">
					<h2>Thanks!</h2>
					<p>Your application has been recorded against the email address <strong><?php echo e($user['email']) ?></strong>.  We'll be in touch soon (invitations will be sent gradually over the period leading up to the conference, with plenty of time to book travel).</p>
					<p>If you want to change your application, just start again, and we'll open your existing application for editing.</p>
				</div>
			</div>
		</section>
		<?php
	} else {
		?>
		<section class="layer">
			<div class="row">
				<div class="small-12 columns">
					<p>Tickets to Edge cost £100.  The space in the venue is quite limited, and we expect this event to be popular, so we are operating a pre-registration process to allocate tickets fairly and ensure a broad cross-section of the development community is able to attend.</p>
					<p>To register your interest, update an existing registration, or claim a reserved ticket, first sign in with a Google account.</p>
				</div>
			</div>
			<form class='register' action='register.php' method='post'>
				<div class="row form-field">
					<div class="small-12 large-4 columns">
						<label>Event</label>
					</div>
					<div class='small-12 large-8 columns'>
						Edge 3, in London, United Kingdom, on 21st March 2014
					</div>
				</div>
				<div class="row form-field">
					<div class="small-12 large-4 columns">
						<label for='txtemail'>Email address</label>
					</div>
					<div class='small-12 large-8 columns'>
						<?php
						if (!empty($user)) {
							echo $user['email']." (<a href='/logout.php?redir=".$_SERVER['REQUEST_URI']."'>Not correct?</a>)";
							echo "<input type='hidden' name='email' value='".e($user['email'])."'/>";
						} else {
							?>
							<a href='<?php echo $auth->getAuthRedirectUrl() ?>'><img width="200" src="https://developers.google.com/accounts/images/sign-in-with-google.png"></a>
							<span class='note'>To verify your email address, please sign in with a Google account. This reduces the risk of duplicate applications, allows you to track your application, and use conference-day participation tools.  We only ask for access to your email address.  If you don't have a Google account, <a href='https://docs.google.com/forms/d/1YIg9mSjfUUje-_hPKi13Mh-fY84pefn1DerYruvOi-w/viewform'>use this form instead</a>.</span>
							<?php
						}
						?>
					</div>
				</div>
				<?php

				if (!empty($data)) {
					?>
					<div class='row'>
						<div class='small-12 columns'>
							<p class='alert'><?php

							if ($data['tickettype']) {
								echo "<strong>You're coming!</strong>  You already have a ticket, and we're looking forward to seeing you at Edge conf.  Need to cancel?  Use the link in your Eventbrite confirmation email or <a href='mailto:hello@edgeconf.com'>email us</a>.";
								$user = null;
							} else if ($data['invitecode']) {
								echo "<strong>Ticket waiting for you!</strong>  Your account has an invitation available to use immediately.  Would you like to <a href='https://www.eventbrite.co.uk/e/".EVENTBRITE_EID."?access=".$data['invitecode']."'>buy your ticket now on Eventbrite</a>?";
								$user = null;
							} else {
								echo "We already have a registration on file for you.  If you'd like to edit your details or session proposals, please update them in the form below.  If your application has been reviewed already it may be queued for review again.";
							}
							?></p>
						</div>
					</div>
					<?php
				}

				?>
				<div class="row form-field<?php if (empty($user)) echo ' disabled'; ?>">
					<div class="small-12 large-4 columns">
						<label for='txtgivenname'>Given name</label>
					</div>
					<div class='small-12 large-8 columns'>
						<input type='text' name='givenname' id='txtgivenname' placeholder='Usually your first name' value='<?php echo e($data,'givenname') ?>'>
					</div>
				</div>
				<div class="row form-field<?php if (empty($user)) echo ' disabled'; ?>">
					<div class="small-12 large-4 columns">
						<label for='txtfamilyname'>Family name</label>
					</div>
					<div class='small-12 large-8 columns'>
						<input type='text' name='familyname' id='txtfamilyname' placeholder='Usually your last name' value='<?php echo e($data,'familyname') ?>'>
					</div>
				</div>
				<div class="row form-field<?php if (empty($user)) echo ' disabled'; ?>">
					<div class="small-12 large-4 columns">
						<label for='seltravelorigin'>Location</label>
					</div>
					<div class='small-12 large-8 columns'>
						<select name='travelorigin' id='seltravelorigin'>
						    <option value=""></option>
						    <?php

						    foreach ($countries as $iso => $name) {
						    	echo "<option value='".$iso."'".((isset($data['travelorigin']) and $iso == $data['travelorigin'])?' selected':'').">".htmlspecialchars($name)."</option>";
						    }

						    ?>
						    <option value="--">Rather not say</option>
	   					</select>
						<span class='note'>Choose the ISO 3166 country you are most likely to be travelling from to attend the conference.  We use this to give a slight preference to locals.</span>
					</div>
				</div>
				<div class="row form-field<?php if (empty($user)) echo ' disabled'; ?>">
					<div class="small-12 large-4 columns">
						<label for='txtorg'>Organisation/affiliation</label>
					</div>
					<div class='small-12 large-8 columns'>
						<input type='text' name='org' id='txtorg' placeholder='eg. IBM' value='<?php echo e($data,'org') ?>'>
						<div class='note'>Your company, school or institution name. We try to avoid having too many delegates from the same company, and this will also get printed on your name badge</div>
					</div>
				</div>
				<div class="row form-field<?php if (empty($user)) echo ' disabled'; ?>">
					<div class="small-12 large-4 columns">
						<label>Sessions of interest</label>
					</div>
					<div class='small-12 large-8 columns sessionchks'>
						<div class='note'>Which sessions interest you the most?  Choose up to four.</div>
						<?php

						foreach ($sessions as $id=>$name) {
							echo "<label class='inline'><input type='checkbox' class='sessionchk' name='sessions[]' value='".$id."'".((isset($data['sessions']) and in_array($id, $data['sessions']))?' checked':'').">".e($name)."</label>";
						}

						?>
					</div>
				</div>
				<div class="row form-field<?php if (empty($user)) echo ' disabled'; ?>">
					<div class="small-12 large-4 columns">
						<label>Expertise</label>
					</div>
					<div class='small-12 large-8 columns'>
						<div class='note'>What kind of experience or expertise can you bring to the sessions that most interest you?  We'll use this to prioritise invitations for those who have the most to contribute on the day, using an anonymised selection process.</div>
						<?php

						foreach ($sessions as $id=>$name) {
							echo "<div class='proposal' id='proposalgroup_".$id."'><label class='sublabel' for='txtproposal_".$id."' id='lblproposal_".$id."'>".e($name)."</label><textarea name='proposal_".$id."' id='txtproposal_".$id."'>".e($data,'proposal_'.$id)."</textarea></div>";
						}

						?>
						<div class='note' id='noproposals'>Choose some sessions above in order to answer this question.</div>
					</div>
				</div>
				<div class="row form-field<?php if (empty($user)) echo ' disabled'; ?>">
					<div class='small-12 columns'>
						<p>Before you submit, make sure you all the details are correct.  Your application will be queued for review.</p>
						<p style='text-align:right'><input type='submit' class='button' value='Submit' /></p>
					</div>
				</div>
			</form>
		</section>
		<?php
	}
	?>

	<footer>
		<div class="row">
			<div class="small-12 columns">
				<p>©2013 Edge contributors. Site constructed by <a href="http://www.thepixelbots.com">Pixelbots</a> and licensed under a <a href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_GB">Creative Commons License</a>.</p>
			</div>
		</div>
	</footer>

  <script>
  document.write('<script src=' +
  ('__proto__' in {} ? 'javascripts/vendor/zepto' : 'javascripts/vendor/jquery') +
  '.js><\/script>')
  </script>

  <script src="javascripts/foundation/foundation.js"></script>

	<script src="javascripts/foundation/foundation.alerts.js"></script>

	<script src="javascripts/foundation/foundation.clearing.js"></script>

	<script src="javascripts/foundation/foundation.cookie.js"></script>

	<script src="javascripts/foundation/foundation.dropdown.js"></script>

	<script src="javascripts/foundation/foundation.forms.js"></script>

	<script src="javascripts/foundation/foundation.joyride.js"></script>

	<script src="javascripts/foundation/foundation.magellan.js"></script>

	<script src="javascripts/foundation/foundation.orbit.js"></script>

	<script src="javascripts/foundation/foundation.placeholder.js"></script>

	<script src="javascripts/foundation/foundation.reveal.js"></script>

	<script src="javascripts/foundation/foundation.section.js"></script>

	<script src="javascripts/foundation/foundation.tooltips.js"></script>

	<script src="javascripts/foundation/foundation.topbar.js"></script>

	<script>
		$(document).ready(function() {

			// Reveal mobile nav
			$('header nav i.icon-reorder').click(function() {
				$('header nav ul').fadeToggle(100);
			});
			$('.conf-toggle').click(function() {
				$('.conf-toggle i').toggleClass('icon-caret-down').toggleClass('icon-caret-up');
				$('.conf-list').toggleClass('active');
				return false;
			});
		});
	</script>

  <script>
    $(document).foundation();
  </script>

  <script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-36962287-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();


  // Form validation
  $(function() {
  	$('form.register').submit(function(e) {
  	  var errorhtml = '<div class="note error">You forgot this one</div>';
  	  $('.error').remove();
      if (!$('#txtgivenname').val()) $('#txtgivenname').parent().append(errorhtml);
      if (!$('#txtfamilyname').val()) $('#txtfamilyname').parent().append(errorhtml);
      if (!$('#seltravelorigin').val()) $('#seltravelorigin').parent().append(errorhtml);
      if (!$('#txtorg').val()) $('#txtorg').parent().append(errorhtml);
      if (!$('.sessionchk:checked').length) $('.sessionchks').append('<div class="note error">Please choose at least one</div>');
      if ($('.sessionchk:checked').length > 4) $('.sessionchks').append('<div class="note error">Too many.  Please choose no more than 4 options</div>');
      $('.proposal:visible').each(function() {
      	if (!$(this).find('textarea').val()) $(this).append(errorhtml);
      });
      if ($('.error').length) {
      	var scrollto = $(".error:eq(0)").offset().top-100;
      	if (scrollto < document.body.scrollTop) {
	      	document.body.scrollTop = scrollto;
	    }
	    e.preventDefault();
      	return false;
      }
  	});
  	$('form.register').on('click change', '.sessionchk', function() {
      $('#proposalgroup_'+this.value)[this.checked?"show":"hide"]();
      $('#noproposals')[$('.proposal:visible').length?"hide":"show"]();
  	});
  	$('.sessionchk:checked').each(function() {
      $('#proposalgroup_'+this.value).show();
      $('#noproposals').hide();
  	})
  });
</script>

</body>
</html>
