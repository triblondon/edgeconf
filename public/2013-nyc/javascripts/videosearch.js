
$.get('videocaptions/videos.json', function(videos) {

  var $iframe = $('iframe#youTubePlayer');
  var $query = $("#query");
  var $numResults = $("#numResults");
  var $resultsList = $("#results");
  var trackPath = "videocaptions/";
  var trackSuffix = ".srt";
  var cues = [];
  var loading = 0;
  var inputTimer;
  var _GET = {};

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
      $.get(trackPath + _id + trackSuffix, function(track) {
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
    if ($iframe.get(0).src.indexOf(cue.videoId) != -1){
      callPlayer("youTubePlayer", "seekTo", [cue.startTime]);
    } else {
      $iframe.get(0).src = "http://www.youtube.com/embed/" + cue.videoid + "?start=" + cue.startTime + "&autoplay=1&enablejsapi=1"
    }
  });


  function loadingComplete() {
    if (_GET.q) {
      $query.val(_GET.q);
      doSearch();
    }
    if (_GET.v && videos[_GET.v]) {
      $iframe.get(0).src = "http://www.youtube.com/embed/" + _GET.v + "?autoplay=1&enablejsapi=1"
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
