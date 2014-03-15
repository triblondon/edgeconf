
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
	});
	$('form.register').on('click change', '#chklistsessions .form-chklist__chk', function() {
		$('#proposalgroup_'+this.value)[this.checked?"show":"hide"]();
		$('#noproposals')[$('.proposal:visible').length?"hide":"show"]();
	});
	$('#chklistsessions .form-chklist__chk:checked').each(function() {
		$('#proposalgroup_'+this.value).show();
		$('#noproposals').hide();
	});
});


// Video search
$(function() {

	var $iframe = $('#videos iframe#youTubePlayer');
	var $query = $("#query");
	var $numResults = $("#numResults");
	var $resultsList = $("#results");
	var player;
	var trackPath = location.pathname.match(/^(\/\d{4}\-\w+)/)[1]+"/video";
	var videos = {};
	var cues = [];
	var loading = 0;
	var inputTimer;
	var _GET = {};

	if (!$iframe.length) return;

	// Bind to player and load track data when the player is ready
	$('body').on('youTubeAPIReady', function() {
		player = new YT.Player('youTubePlayer', {
			events: {
				'onReady': function () {
					$.getJSON(trackPath, init);
				}
			}
		});
	});

	function init(data) {

		videos = data;

		// Recognise query params
		var parts = document.location.search.replace(/^\?/, '').split('&');
		if (parts.length) {
			parts.forEach(function(part) {
				part = part.split('=');
				_GET[part[0]] = part[1];
			})
		}

		// Limit height of results panel to match IFRAME
		$resultsList.css('max-height', Math.max($iframe.height()-80, 150));
		$(window).resize(function() {
			$resultsList.css('max-height', Math.max($iframe.height()-80, 150));
		})

		// Load caption data
		for (var id in videos) {
			loading++;
			(function (_id) {
				$.get(trackPath + '/' + _id, function(track) {
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
	}

	function loadingComplete() {
		if (_GET.q) {
			$query.val(_GET.q);
			doSearch();
		}
		if (_GET.v && videos[_GET.v]) {
			player.loadVideoById(_GET.v, 0);
		}
	}

	function doSearch(){
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

	if (!$('#onair').length || !livePlaylist) return;

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
		for (var i=0, s=livePlaylist.length; i<s; i++) {
			livePlaylist[i].start_time = Date.parse(livePlaylist[i].start_time);
			livePlaylist[i].end_time = Date.parse(livePlaylist[i].end_time);
		}
		setInterval(checkCurrent, 5000);
	}

	function checkCurrent() {
		var now = (new Date()).getTime();
		var current = player.getVideoUrl();
		for (var i=0, s=livePlaylist.length; i<s; i++) {
			if (livePlaylist[i].start_time < now && livePlaylist[i].end_time > now && livePlaylist[i].youtube_id) {
				if (current.indexOf(livePlaylist[i].youtube_id) === -1) {
					cue(livePlaylist[i]);
				}
				$('.videoframe').addClass('playing');
				return;
			}
			$('.videoframe').removeClass('playing');
		}
	}

	function cue(session) {
		player.loadVideoById(session.youtube_id);
		$('#current-session').html(session.name);
	}

});

// It seems pretty ugly that YouTube requires a global variable.  This seems a bit neater.
function onYouTubeIframeAPIReady() {
	$('body').trigger('youTubeAPIReady');
}
