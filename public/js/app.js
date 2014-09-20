
// Foundation
$(document).foundation();


// Gallery
$('.flexslider').flexslider({ animation: "slide" });


// Google Analytics
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-36962287-1']);
_gaq.push(['_trackPageview']);
(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();


// Registration form
$(function() {
	var isdirty = false;
	$('form.register').submit(function(e) {
		var errorhtml = '<div class="note error">You forgot this one</div>';
		$('.error').remove();
		if (!$('#txtgiven_name').val()) $('#txtgiven_name').parent().append(errorhtml);
		if (!$('#txtfamily_name').val()) $('#txtfamily_name').parent().append(errorhtml);
		if (!$('#seltravel_origin').val()) $('#seltravel_origin').parent().append(errorhtml);
		if (!$('#txtorg').val()) $('#txtorg').parent().append(errorhtml);
		if (!$('#chklistsessions .form-chklist__chk:checked').length) $('#chklistsessions').append('<div class="note error">Please choose at least one</div>');
		if ($('#chklistsessions .form-chklist__chk:checked').length > 4) $('#chklistsessions').append('<div class="note error">Too many.  Please choose no more than 4 options</div>');
		$('.proposal:visible').each(function() {
			if (!$(this).find('textarea').val()) $(this).append(errorhtml);
		});
		if ($('.error').length) {
			var scrollto = $(".error:eq(0)").offset().top-100;
			if (scrollto < document.documentElement.scrollTop) {
				document.documentElement.scrollTop = scrollto;
			}
			e.preventDefault();
			return false;
		}
		isdirty = false;
	});
	$('form.register').on('click change', '#chklistsessions .form-chklist__chk', function() {
		$('#proposalgroup_'+this.value)[this.checked?"show":"hide"]();
		$('#noproposals')[$('.proposal:visible').length?"hide":"show"]();
	});
	$('#chklistsessions .form-chklist__chk:checked').each(function() {
		$('#proposalgroup_'+this.value).show();
		$('#noproposals').hide();
	});

	$('form.register').on('click change', 'input, select', function() {
		isdirty = true;
	});
	$(window).on('beforeunload', function(e) {
		var msg = 'If you navigate away from this page any unsaved information will be lost.';
		if (!isdirty) return;
		e = e || window.event;
		if (e) e.returnValue = msg;
		return msg;
	});

	$('.email-verify-button').click(function() {
		$('.email-field .error').remove();
		if (!/^\S+@\S+\.\S+$/.test($('#txtemail').val())) {
			$('.email-verify-button').after('<div class="note error"><em>Probably</em> not a valid email address</div>');
			return;
		}
		this.blur();
		$('#txtemail').attr('disabled', 'disabled');
		$.ajax({
			type: 'POST',
			url: '/auth/email/start-verify',
			data: {email:$('#txtemail').val()},
			success: function() {
				$('.email-verify-button').hide();
				$('.email-verify-code').show();
				$('#txtemailverify').focus();
			},
			error: resetEmailField
		});
	});
	$('.email-verify-reset-link').click(resetEmailField);
	function resetEmailField() {
		$('.email-verify-code').hide().find('input:text').val('');
		$('.email-verify-button').show();
		$('#txtemail').removeAttr('disabled').focus();
	}

	$('.email-verify2-button').click(function() {
		$('.email-field .error').remove();
		$.ajax({
			type: 'POST',
			url: '/auth/email/verify',
			data: {email:$('#txtemail').val(),code:$('#txtemailverify').val()},
			success: function() {
				isdirty = false;
				window.location.reload();
			},
			error: function() {
				$('.email-verify-code').after('<div class="note error">Incorrect code</div>');
			}
		});
	});

});


// Video search
$(function() {

	var $iframe;
	var $query = $("#query");
	var $numResults = $("#numResults");
	var $resultsList = $("#results");
	var player;
	var trackPath;
	var videos = {};
	var cues = [];
	var loading = 0;
	var inputTimer;
	var _GET = {};
	var defaultvid;

	if (!$('#videos #youTubePlayer').length) return;

	trackPath = location.pathname.match(/^(\/\d{4}\-\w+)/)[1]+"/video";

	// Bind to player and load track data when the player is ready
	$('body').on('youTubeAPIReady', function() {
		console.log('YT API ready');
		player = new YT.Player('youTubePlayer', {
			events: {
				'onReady': function () {
					console.log('YT player ready');
					$iframe = $(player.getIframe());
					$.getJSON(trackPath, init);
				}
			}
		});
		window.YTplayer = player;
	});

	function init(data) {

		console.log("Track index loaded");
		videos = data;

		// Recognise query params
		var parts = document.location.search.replace(/^\?/, '').split('&');
		if (parts.length) {
			parts.forEach(function(part) {
				part = part.split('=');
				_GET[part[0]] = part[1];
			})
		}

		// Load caption data
		for (var id in videos) {
			if (!defaultvid) defaultvid = id;
			loading++;
			(function (_id) {
				$.get(trackPath + '/' + _id, function(track) {
					console.log("Track content loaded", videos[_id]);
					var lines = track.replace(/(\r\n|\n\r|\n|\r)/, "\n").split('\n');
					var currentCue = {videoid: _id, text:''};
					var timings;
					lines.forEach(function(line) {

						// Cue ID (i.e. just digits)
						if (line.match(/^\d+\s*$/)){
							if (currentCue.text) cues.push(currentCue);
							currentCue = {"videoid": _id, text:''};

						// Time index
						} else if (timings = line.match(/\d\d:\d\d:\d\d.\d\d\d/)) {
							var split = timings[0].match(/\d{2}/g);
							currentCue.startTime = (parseInt(split[0],10) * 3600) + (parseInt(split[1], 10) * 60) + parseInt(split[2], 10);

						// Text
						} else {
							currentCue.text += ' '+line.replace(/\n/, " ").trim();
						}
					});
					cues.push(currentCue);

					loading--;
					if (!loading) loadingComplete();
				});
			}(id));
		}

		// Bind to search input
		$query.bind('input', function() {
			if ($(this).val().length < 2) {
				$resultsList.empty();
				$numResults.empty();
				$('#videos .help-block').show();
				return false;
			}
			if (inputTimer) clearTimeout(inputTimer);
			inputTimer = setTimeout(doSearch, 300);
		});

		// Bind to cues
		$('#videos').on('click', '.cue', function() {
			var cue = cues[$(this).attr('data-index')];
			if (player.getVideoUrl().indexOf(cue.videoId) != -1){
				player.seekTo(cue.startTime, true);
			} else {
				player.loadVideoById(cue.videoid, cue.startTime);
			}
		});

		// Bind to window size changes
		$(window).resize(resize);

	}

	function loadingComplete() {
		if (_GET.q) {
			$query.val(_GET.q);
			doSearch();
		}
		if (_GET.v && videos[_GET.v]) {
			player.loadVideoById(_GET.v, 0);
		} else {
			console.log(defaultvid);
			player.cueVideoById(defaultvid);
		}
		resize();
	}

	// Limit height of results panel to match IFRAME
	function resize() {
		$resultsList.css('max-height', Math.max($iframe.height()-80, 150));
	}

	function doSearch() {
		var querystr = $query.val(), numResults = 0, maxresults = 100, currentVid = '';
		if (querystr.length <= 2) return;
		document.querySelector("*").style.cursor = "wait";
		$('#videos .help-block').hide();
		$numResults.html("Searching...");
		$resultsList.empty();
		cues.forEach(function(cue, i) {
			var re = new RegExp(querystr, "i");
			if (re.test(cue.text)) {
				numResults++;
				if (numResults < maxresults) {
					if (currentVid !== cue.videoid) {
						$resultsList.append('<li class="video-heading">'+videos[cue.videoid]+'</li>');
						currentVid = cue.videoid;
					}
					$resultsList.append('<li class="cue" data-index="'+i+'"><span class="cueStartTime">' + toHoursMinutesSeconds(cue.startTime) + ':</span> <span class="cueText">' + cue.text.replace(new RegExp("("+querystr+")", "gi"), "<em>$1</em>") + '</span>');
				}
			}
		});
		if (numResults > maxresults) {
			$resultsList.append('<li class="excess">+ '+(numResults-maxresults)+' more (too many to show)</span>');
		}

		$numResults.html(numResults + " result(s)");
		document.querySelector("*").style.cursor = "";
	}

	// Convert decimal time to hh:mm:ss
	// e.g. convert 123.3 to 2:03
	function toHoursMinutesSeconds(decimalSeconds){
		var hours = Math.floor(decimalSeconds/3600);
		var mins = Math.floor((decimalSeconds - hours * 3600)/60);
		var secs = Math.floor(decimalSeconds % 60);
		if (secs < 10) secs = "0" + secs;
		if (mins < 10) mins = "0" + mins;
		if (hours < 10) hours = "0" + hours;
		return hours + ":" + mins + ":" + secs;
	}
});

// Live player
$(function() {

	var player;

	var livePlaylistHack = [
	{
		"name": "Registration and breakfast",
		"start_time": "2014-09-20 09:00:00 -0700",
		"end_time": "2014-09-20 09:45:00 -0700",
		"youtube_id": null
	},
	{
		"name": "Welcome",
		"start_time": "2014-09-20 09:45:00 -0700",
		"end_time": "2014-09-20 10:00:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	},
	{
		"name": "Installable apps and permissions",
		"start_time": "2014-09-20 10:00:00 -0700",
		"end_time": "2014-09-20 11:00:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	},
	{
		"name": "Layout performance",
		"start_time": "2014-09-20 11:00:00 -0700",
		"end_time": "2014-09-20 12:00:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	},
	{
		"name": "Morning break",
		"start_time": "2014-09-20 12:00:00 -0700",
		"end_time": "2014-09-20 12:15:00 -0700",
		"youtube_id": null
	},
	{
		"name": "Security and Identity",
		"start_time": "2014-09-20 12:15:00 -0700",
		"end_time": "2014-09-20 13:15:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	},
	{
		"name": "Lunch",
		"start_time": "2014-09-20 13:15:00 -0700",
		"end_time": "2014-09-20 14:15:00 -0700",
		"youtube_id": null
	},
	{
		"name": "Package management",
		"start_time": "2014-09-20 14:15:00 -0700",
		"end_time": "2014-09-20 15:15:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	},
	{
		"name": "Image formats",
		"start_time": "2014-09-20 15:15:00 -0700",
		"end_time": "2014-09-20 16:15:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	},
	{
		"name": "Afternoon break",
		"start_time": "2014-09-20 16:15:00 -0700",
		"end_time": "2014-09-20 16:30:00 -0700",
		"youtube_id": null
	},
	{
		"name": "Breakout",
		"start_time": "2014-09-20 16:30:00 -0700",
		"end_time": "2014-09-20 17:30:00 -0700",
		"youtube_id": null
	},
	{
		"name": "Standards and the extensible web manifesto",
		"start_time": "2014-09-20 17:30:00 -0700",
		"end_time": "2014-09-20 18:30:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	},
	{
		"name": "Closing remarks and thanks",
		"start_time": "2014-09-20 18:30:00 -0700",
		"end_time": "2014-09-20 18:45:00 -0700",
		"youtube_id": "eqfAZ4WeS3g"
	}
	];

	if (!$('#onair').length || !livePlaylistHack) return;

	$('body').on('youTubeAPIReady', function() {
		player = new YT.Player(
			'youTubePlayer', {
			events: {
				'onStateChange': checkCurrent,
				'onReady': onPlayerReady
			}
		});
	});

	function onPlayerReady(evt) {
		for (var i=0, s=livePlaylistHack.length; i<s; i++) {
			livePlaylistHack[i].start_time = Date.parse(livePlaylistHack[i].start_time);
			livePlaylistHack[i].end_time = Date.parse(livePlaylistHack[i].end_time);
		}
		checkCurrent();
		setInterval(checkCurrent, 5000);
	}

	function checkCurrent() {
		var now = (new Date()).getTime();
		//var nowUtc = Date.UTC(now.getUTCFullYear(),now.getUTCMonth(), now.getUTCDate(), now.getUTCHours(), now.getUTCMinutes(), now.getUTCSeconds(), now.getUTCMilliseconds());
		var current = player.getVideoUrl();

		for (var i=0, s=livePlaylistHack.length; i<s; i++) {
			if (livePlaylistHack[i].start_time < now && livePlaylistHack[i].end_time > now) {
				if (current.indexOf(livePlaylistHack[i].youtube_id) === -1) {
					cue(livePlaylistHack[i]);
				}
				
				$('#current-session').html(livePlaylistHack[i].name);
				return;
			}
		}

		$('.videoframe').removeClass('playing');
	}

	function cue(session) {
		if (session.youtube_id !== null) {
			$('.videoframe').addClass('playing');
			player.loadVideoById(session.youtube_id);	
		}
	}

});

// It seems pretty ugly that YouTube requires a global variable.  This seems a bit neater.
function onYouTubeIframeAPIReady() {
	$('body').trigger('youTubeAPIReady');
}


// Table filter
$('input[data-filter-for]').each(function() {
	var t, inp = $(this);
	inp.on('keyup', function() {
		clearTimeout(t);
		t = setTimeout(search, 300);
	});
	function search() {
		var query = inp.val().toLowerCase();
		var table = $('#'+inp.attr('data-filter-for'));
		var matching;
		if (query) {
			matching = table.find('.filterable[data-filter-value*="'+query+'"]');
			matching.show();
			table.find('.filterable').not(matching).hide();
		} else {
			table.find('.filterable').show();
		}
	}
})
