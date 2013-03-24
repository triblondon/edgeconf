
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Edge &middot; A conference on advanced web technologies</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Edge is a one-day conference for web developers and browser vendors.">

    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
    <style>

    /* Lighter body text */
    body { color: #5a5a5a; }
    h2 { font-size: 50px; font-weight: 300; line-height: 1; letter-spacing: -1px; margin-bottom: 1em; }
    hr { margin: 40px 0; }

    /* Container colours */
    .wrap { padding: 40px 0 }
    .wrap-green { background: #165016; color: #ddd;
      background: -moz-linear-gradient(top, #0c2b0c 0%, #165016 150px);
      background: -webkit-gradient(linear, left top, left 150px, color-stop(0%,#0c2b0c), color-stop(100%,#165016));
      background: -webkit-linear-gradient(top, #0c2b0c 0%,#165016 150px);
      background: -o-linear-gradient(top, #0c2b0c 0%,#165016 150px);
      background: -ms-linear-gradient(top, #0c2b0c 0%,#165016 150px);
      background: linear-gradient(to bottom, #0c2b0c 0%,#165016 150px);
    }
    .wrap-blue { background: #1d154f; color: #ddd;
      background: -moz-linear-gradient(top, #080133 0%, #1d154f 150px);
      background: -webkit-gradient(linear, left top, left 150px, color-stop(0%,#080133), color-stop(100%,#1d154f));
      background: -webkit-linear-gradient(top, #080133 0%,#1d154f 150px);
      background: -o-linear-gradient(top, #080133 0%,#1d154f 150px);
      background: -ms-linear-gradient(top, #080133 0%,#1d154f 150px);
      background: linear-gradient(to bottom, #080133 0%,#1d154f 150px);
    }
    .wrap-grey { background-color: #1a1a1a; color: #ddd;
      background: -moz-linear-gradient(top, #000000 0%, #1a1a1a 150px);
      background: -webkit-gradient(linear, left top, left 150px, color-stop(0%,#000000), color-stop(100%,#1a1a1a));
      background: -webkit-linear-gradient(top, #000000 0%,#1a1a1a 150px);
      background: -o-linear-gradient(top, #000000 0%,#1a1a1a 150px);
      background: -ms-linear-gradient(top, #000000 0%,#1a1a1a 150px);
      background: linear-gradient(to bottom, #000000 0%,#1a1a1a 150px);
    }
    .wrap-black { background-color: #080808; color: #999 }

    /* Navbar */
    .navbar-wrapper { position: fixed; top: 10px; width: 100%; z-index: 10; }
    .navbar-wrapper .navbar { opacity: 0.95; }

    /* Hero unit: accomodate floating nav */
    .hero-unit { padding-top: 120px; margin-bottom: 60px; background: #333 url(/img/herobg2.jpg) 50% 50% no-repeat; background-size: cover; position: relative; color: white; overflow: hidden }
    .hero-unit .hero-title { float:left; text-shadow: 0px 0px 8px black; }
    .hero-unit .hero-title img { width: 220px; margin-left: -10px }
    .hero-unit .hero-detail { float: right; background: rgba(255, 255, 255, 0.8); padding: 10px 20px; border-radius: 6px; text-align: right; color: #333; text-shadow: none; }
    #sold-out-notice { font-weight: bold; text-align: center; padding: 10px; }
    #emailsignup { margin-top: 15px; }
    #emailsignup label { display: block; font-weight: bold; }
    #emailsignup .input-xlarge { margin-bottom: 0; }
    #emailsignup .help-block { font-size: 80%; margin: 5px 0 10px 0; line-height: normal; }

    .share-widgets { margin-top: 20px; overflow: hidden; height: 22px; position: relative; }
    .share-widgets > * { display: block; position: absolute; top:0 }
    .fb-like { left: 0 }
    .twitter-share-button { left: 100px }
    #___plusone_0 { left: 210px }

    /* Sessions */
    #schedule a { color: #beb }
    #schedule a:hover { color:#2bff47 }
    .session-time { background: black; color: white; font-weight: bold; position: relative; font-size: 20px; padding: 7px 10px 7px 15px; display: inline-block; margin-top: 5px; }
    .session-time i.ch { font-size: 0; height: 0; line-height: 0; border-style: solid; border-color: transparent; border-width: 17px 0 17px 17px; border-left-color: black; position: absolute; right: -17px; top: 0; }
    .session .headshots img { width: 50px; height: 50px; border: 1px solid #777; padding: 2px; margin: 1px 6px 1px 1px; }
    .session .headshots .speaker img { border-color: white; margin: 0 6px 0 0; }
    .session h3 { margin:0; font-weight: normal; }
    .session { padding: 20px 0; }
    .session:nth-child(2n) { background-color: rgba(255,255,255,0.1); }
    .spec-links { margin: 0 0 10px 0; list-style-type: none; }
    .spec-links li { display: inline-block; padding: 0; margin: 0 10px 0 0; font-size: 90%; }

    .format-fig { text-align: center }

    /* Speakers */
    .speakers { margin-top: 10px; }
    .speakers .speaker { padding-left: 110px; overflow: hidden; }
    .speakers .speaker img { width: 90px; height: 90px; padding: 2px; border: 1px solid #999; float: left; margin-left: -110px; margin-top: 7px; }
    .speakers .speaker h3 { font-weight: normal; margin: 0; line-height: 130% }
    .speakers .speaker .affil { color: #bbe; }

    /* Venue */
    .nearest-tube { margin-left: 0; margin-bottom: 20px; }
    .nearest-tube li { padding: 2px 0 2px 22px; list-style-type: none; background: url(/img/london-underground.png) 0 50% no-repeat; }

    /* FAQs */
    .faq dt { margin-top: 15px; }
    .faq dd { margin-bottom: 10px; }

    /* Footer */
    footer { padding-top: 30px; }

    /* Signup */
    #ifrsignup { width: 100%; height: 400px; }
    @media (min-width: 768px) {
      .modal { width: 700px; margin-left: -350px }
    }
    .modal-body { overflow:hidden; max-height: none }

    /* Video search */
    #video { margin-top: 40px; }
    .videoframe { width: 100%; height: 0; padding-top: 56.25%; position: relative; margin-bottom: 10px; }
    .videoframe > *:first-child { position: absolute; display: block; top:0; left:0; right:0; bottom:0; width: 100%; height: 100%; }

    div#queryExplanation{
    color: #ddd;
    margin: 0 0.7em 1em 0;
    }

    div#numResults {
    color: darkRed;
    margin: 0 0 1em 0;
    }

    div#results {
    max-height: 300px;
    overflow-y: auto;
    }

    div.cue {
    cursor: pointer;
    font-size: 10pt;
    margin: 0 0 0.5em 0;
    line-height: 1.3em;
    }

    div.cues {
    margin: 1em 0 0 0;
    padding: 0 0 0 1em;
    }

    div.video {
    margin: 0 0 1.3em 0;
    border-bottom: 1px dotted #999;
    padding: 0 0 0.6em 0;
    }

    div.video div.videoRating {
    color: #666;
    font-size: 10pt;
    margin: 0 0 0.4em 0;
    clear: left;
    }

    div.video div.videoViewCount {
    border-bottom: 1px solid #333;
    color: #666;
    font-size: 10pt;
    margin: 0 0 0.6em 0;
    padding: 0 0 0.6em 0;
    }

    div.video div.speakers {
    color: #999;
    font-size: 11pt;
    margin: 0 0 -0.1em 0;
    padding: 0 0 0 16px;
    }

    div.video div.videoSummary {
    color: #ddd;
    font-size: 11pt;
    margin: 0 0 5px 0;
    line-height: 1.3em;
    padding: 28px 0 0 0;
    }

    div.video div.videoTitle {
    color: #ccc;
    font-size: 12pt;
    margin: 0 0 0.4em 0;
    }

    div.watchVideo {
      color: #beb;
      cursor: pointer;
      font-weight: bold;
      margin: 0 0 0.5em 0;
    }

    div.watchVideo:hover {
      color: #2bff47;
    }

    div.watchVideo span:hover {
      text-decoration: underline;
    }

    span.cueText em {
    font-style: normal;
    background: rgba(224, 79, 23, 0.49);
    }

    img.videoThumbnail {
    float: left;
    height: 180px;
    margin: 9px 10px 10px 0;
    width: 240px;
    }

    span.cueStartTime {
    color: #aaa;
    }

    span.cueText {
    color: #ccc;
    }

    span.cueText:hover {
    color: white;
    }

    span.inputLabel {
    color: #eee;
    float: left;
    font-size: 11pt;
    font-family: Arial, sans-serif;
    line-height: 1em;
    }

    summary{
    color: white;
    cursor: pointer;
    font-weight: bold;
    outline: none;
    }

    summary.videoTitle {
    display: block; /* for browsers that don't support <summary> */
    margin: 0 0 0 0;
    padding: 0 0 0 18px;
    text-indent: -18px;
    line-height: 1.2em;
    }


    /* RESPONSIVE CSS */

    @media (max-width: 979px) {
      .navbar-wrapper { margin-bottom: 0; width: auto; position: static; }
      .navbar-wrapper .navbar { margin: 0; }
      .navbar-inner { border-radius: 0; margin: 0; }
    }

    @media (max-width: 767px) {
    body { padding: 0; }
    .hero-unit { border-radius: 0; padding: 35px 15px 15px 15px; text-align: center }
    .row, h2, dl.faq, footer { padding-left: 15px; padding-right: 15px }
    .format-fig { display: none }
    .hero-unit .hero-title { float: none; text-shadow: 2px 0px 2px black }
    .hero-unit .hero-detail { float: none; display:inline-block; clear:left; text-align:center; margin: 0 auto; }
    .hero-unit .hero-detail p { display: inline; text-align:left }
    .benefits { display: none }
    }

    </style>


    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="shortcut icon" href="/favicon.ico">
  </head>

  <body data-spy="scroll" data-target=".navbar">

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=181071245369997";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
<script type="text/javascript">
  window.___gcfg = {lang: 'en-GB'};

  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>

   <div class='navbar-wrapper'>
    <div class="container">
      <div class="navbar navbar-inverse">
        <div class="navbar-inner">
          <!-- Responsive Navbar Part 1: Button for triggering responsive navbar (not covered in tutorial). Include responsive CSS to utilize. -->
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#top">Edge</a>
          <!-- Responsive Navbar Part 2: Place all navbar contents you want collapsed withing .navbar-collapse.collapse. -->
          <div class="nav-collapse collapse">
            <ul class="nav">
              <li class="active"><a href="#about">About</a></li>
              <li><a href="#video">Videos</a></li>
              <li><a href="#schedule">Schedule</a></li>
              <li><a href="#format">Session format</a></li>
              <li><a href="#panellists">Panellists</a></li>
              <li><a href="#venue">Venue</a></li>
              <li><a href="#faq">FAQs</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div><!-- /.navbar-inner -->
      </div><!-- /.navbar -->
    </div><!-- /.container -->
   </div><!-- /.navbar-wrapper -->

  <div class="container" id='about'>

  <div class="hero-unit">
  <div class='hero-title'>
      <h1><img src='img/logo.png' alt='Edge' /></h1>
      <p>Building out from the edge of web technology</p>
  </div>
  <div class='hero-detail'>
    <p>London, 9th February 2013</p>
    <p class="alert alert-error" id='sold-out-notice'>SOLD OUT!</p>
  </div>
  </div>

  <div class='row'>
    <div class='span6'>
      <p class='lead'>A new kind of one-day conference on advanced web technologies for developers and browser vendors.</p>
      <p>Presented by <a href='http://labs.ft.com'>FT Labs</a>, <a href='https://developers.google.com/'>Google</a>, and <a href='http://facebook.com/facebooklondon'>Facebook</a>.</p>
      <div class='share-widgets'>
        <div class="fb-like" data-href="http://edgeconf.com" data-send="false" data-layout="button_count" data-width="450" data-show-faces="true"></div>
        <a href="https://twitter.com/share" class="twitter-share-button" data-url="http://edgeconf.com" data-via="edgeconf" data-dnt="true"></a>
        <div class="g-plusone" data-size="medium" data-href="http://edgeconf.com"></div>
      </div>
    </div>
    <div class='span6'>
      <form id='emailsignup' action="http://assanka.us1.list-manage1.com/subscribe/post" method="post">
        <input type="hidden" name="u" value="b17e923e9f871f8fc40327494">
        <input type="hidden" name="id" value="0c9bd2c13d">
        <label for='mailchimpemail'>Sign up for news:</label>
        <input id='mailchimpemail' class='input-xlarge' type="email" autocapitalize="off" autocorrect="off" name="MERGE0" id="MERGE0" size="25" placeholder='your email address' /> <button type='submit' class='btn'>Sign up</button>
        <span class='help-block'>You'll be added to our Mailchimp list and receive occasional updates about Edge events.</span>
      </form>
    </div>
  </div>
  </div>

  <div class='wrap wrap-grey' id='video'>
  <div class='container'>

    <h2>Videos from the conference</h2>

    <div class='row'>
      <div class='span8'>
        <div class='videoframe'>
          <iframe id='youTubePlayer' src='http://www.youtube.com/embed/videoseries?list=PLNYkxOF6rcICCU_UD67Ga0qLvMjnBBwft&amp;html5=1' frameborder='0'></iframe>
        </div>
      </div>
      <div class='span4'>
        <label for='query'>Search video transcripts:</label><input id="query" type="text" class='input-block-level' />
        <span class='help-block'>Enter text to search transcripts, then click on a result to view video.</span>
        <div id="numResults"></div>
        <!-- begin results container, content generated dynamically -->
        <div id="results"></div>
        <!-- end results container, content generated dynamically -->
      </div>
    </div>

  </div>
  </div>

  <div class='wrap wrap-green' id='schedule'>
  <div class="container">

  <h2>Schedule</h2>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T09:00:00Z'>09:00</time><i class='ch'></i></div></div>
    <div class='span10'>
      <h3>Registration and breakfast</h3>
      <p>Doors open to all participants at 9am.  Arrive early to take advantage of breakfast provided by Facebook London.</p>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T09:50:00Z'>09:50</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>Welcome</h3>
      <p>An introduction to the unique format of Edge, and how you can participate.</p>
    </div>
    <div class='span4 headshots'>
    <a href='#panellists-andrew-betts'><img src='img/heads/andrew-betts.jpg' title='Andrew Betts' /></a>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T10:00:00Z'>10:00</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>1. Offline</h3>
      <p>What's the <em>right</em> way to build offline into a web application?  Despite wide support of technologies like appcache and web storage, solutions remain hackish.  Why?</p>
      <ul class='spec-links'>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.whatwg.org/specs/web-apps/current-work/multipage/offline.html#appcache'>App Cache</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.whatwg.org/specs/web-apps/current-work/multipage/webstorage.html'>Web Storage</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://dvcs.w3.org/hg/quota/raw-file/tip/Overview.html'>Quota API</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.w3.org/TR/file-system-api/'>Filesystem API</a></li>
      </ul>
    </div>
    <div class='span4 headshots'>
    <p>
      <a href='#panellists-jake-archibald' class='speaker'><img src='img/heads/jake-archibald.jpg' title='Jake Archibald (opener)' /></a>
      <a href='#panellists-tobie-langel'><img src='img/heads/tobie-langel.jpg' title='Tobie Langel' /></a>
      <a href='#panellists-alex-russell'><img src='img/heads/alex-russell.jpg' title='Alex Russell' /></a>
      <a href='#panellists-mark-christian'><img src='img/heads/mark-christian.jpg' title='Mark Christian' /></a>
    </p>
    <p>Moderator: <a href="#panellists-andrew-betts">Andrew Betts</a></p>
    <div class="watchVideo" data-topic="offline"><i class='icon-play-circle icon-white'></i> <span>Watch video</span></div>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T11:00:00Z'>11:00</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>2. Network</h3>
      <p>It's easy to poke fun at websites with multi-megabyte pages, but latency and number of round trips are the biggest killers of page load performance, especially over 3G.  How can we get the best out of the network and not let it slow down our apps?  What's the best way to handle foreign resources, dependency management, batching and minification?</p>
      <ul class='spec-links'>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.w3.org/TR/netinfo-api/'>Net-info API</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.whatwg.org/specs/web-apps/current-work/multipage/network.html#network'>Websockets</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.w3.org/TR/navigation-timing/'>Navigation timing</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://developers.google.com/closure/compiler/'>Closure compiler</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://github.com/mishoo/UglifyJS'>UglifyJS</a></li>
        <li><i class="icon-asterisk icon-white"></i> Build tools</li>
     </ul>
    </div>
    <div class='span4 headshots'>
    <p>
      <a href='#panellists-ilya-grigorik' class='speaker'><img src='img/heads/ilya-grigorik.png' title='Ilya Grigorik (opener)' /></a>
      <a href='#panellists-jackson-gabbard'><img src='img/heads/jackson-gabbard.png' title='Jackson Gabbard' /></a>
      <a href='#panellists-john-cleveley'><img src='img/heads/john-cleveley.jpg' title='John Cleveley' /></a>
      <a href='#panellists-andy-davies'><img src='img/heads/andy-davies.jpg' title='Andy Davies' /></a>
    </p>
    <p>Moderator: <a href='#panellists-steve-thair'>Steve Thair</a></p>
    <div class="watchVideo" data-topic="network"><i class='icon-play-circle icon-white'></i> <span>Watch video</span></div>
    </div>
  </div>

  <div class='row session session-break'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T12:00:00Z'>12:00</time><i class='ch'></i></div></div>
    <div class='span10'>
      <h3>Break</h3>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T12:15:00Z'>12:15</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>3. Performance</h3>
      <p>How can we get faster repaints, more frames per second, quicker layout updates?  Why are in-browser operations still perceptibly slower than native?  And with page session time growing dramatically, are web developers worried enough about memory leaks and garbage collection?</p>
      <ul class='spec-links'>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.whatwg.org/specs/web-apps/current-work/multipage/workers.html'>Web workers</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='http://www.w3.org/TR/performance-timeline/'>Performance timeline</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://developer.mozilla.org/en-US/docs/JavaScript/Memory_Management'>Memory management</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://developer.mozilla.org/en-US/docs/DOM/treeWalker'>TreeWalker</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://developer.mozilla.org/en-US/docs/DOM/NodeIterator'>NodeIterator</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://developer.mozilla.org/en-US/docs/DOM/element.insertAdjacentHTML'>insertAdjacentHTML</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://developer.mozilla.org/en-US/docs/DOM/range.createContextualFragment'>createContextualFragment</a></li>
        <li><i class="icon-asterisk icon-white"></i> <a href='https://developer.mozilla.org/en-US/docs/DOM/MutationObserver'>MutationObservers</a></li>
        <li><i class="icon-asterisk icon-white"></i> H/W acceleration</li>
        <li><i class="icon-asterisk icon-white"></i> DOM avoidance</li>
        <li><i class="icon-asterisk icon-white"></i> Caching patterns</li>
        <li><i class="icon-asterisk icon-white"></i> Perception</li>
      </ul>
    </div>
    <div class='span4 headshots'>
    <p>
      <a href='#panellists-shane-osullivan' class='speaker'><img src='img/heads/shane-osullivan.jpg' title='Shane O&#39;Sullivan' /></a>
      <a href='#panellists-rowan-beentje'><img src='img/heads/rowan-beentje.jpg' title='Rowan Beentje' /></a>
      <a href='#panellists-chris-lord'><img src='img/heads/chris-lord.jpg' title='Chris Lord' /></a>
      <a href='#panellists-pavel-feldman'><img src='img/heads/pavel-feldman.jpg' title='Pavel Feldman' /></a>
    </p>
    <p>Moderator: <a href='#panellists-matt-delaney'>Matt Delaney</a></p>
    <div class="watchVideo" data-topic="performance"><i class='icon-play-circle icon-white'></i> <span>Watch video</span></div>
    </div>
  </div>

  <div class='row session session-break'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T13:15:00Z'>13:15</time><i class='ch'></i></div></div>
    <div class='span10'>
      <h3>Lunch</h3>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T14:15:00Z'>14:15</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>4. Responsive layout</h3>
      <p>Why are some designs easy to implement and others almost impossible?  Can we make it easier to do magazine style column layout, fitted wrapping or embedding sandboxed content?  For those aiming for truly responsive design, are variables like CPU power, viewing distance, input interface and pixel density just as important as viewport-width?</p>
      <ul class='spec-links'>
        <li><a href='http://www.whatwg.org/specs/web-apps/current-work/multipage/the-iframe-element.html#attr-iframe-seamlesse'><i class="icon-asterisk icon-white"></i> Seamless IFRAMEs</a></li>
        <li><a href='http://dev.w3.org/csswg/css3-regions/'><i class="icon-asterisk icon-white"></i> CSS regions</a></li>
        <li><a href='http://www.w3.org/TR/css3-fonts/'><i class="icon-asterisk icon-white"></i> CSS3 fonts</a></li>
        <li><a href='http://www.w3.org/TR/css3-transforms/'><i class="icon-asterisk icon-white"></i> CSS3 transforms</a></li>
        <li><a href='https://dvcs.w3.org/hg/FXTF/raw-file/tip/filters/index.html'><i class="icon-asterisk icon-white"></i> CSS filters</a></li>
        <li><a href='http://github.com/ftlabs/ftcolumnflow'><i class="icon-asterisk icon-white"></i> FT columnflow</a></li>
        <li><a href='http://dvcs.w3.org/hg/webcomponents/raw-file/tip/explainer/index.html'><i class="icon-asterisk icon-white"></i> Web components</a></li>
        <li><a href='http://www.w3.org/TR/css3-flexbox/'><i class="icon-asterisk icon-white"></i> Flexbox</a></li>
        <li><a href='http://www.w3.org/TR/css3-mediaqueries/'><i class="icon-asterisk icon-white"></i> Media queries</a></li>
      </ul>
    </div>
    <div class='span4 headshots'>
    <p>
      <a href='#panellists-george-crawford' class='speaker'><img src='img/heads/george-crawford.jpg' title='George Crawford (opener)' /></a>
      <a href='#panellists-razvan-caliman'><img src='img/heads/razvan-caliman.jpg' title='Razvan Caliman' /></a>
      <a href='#panellists-tab-atkins'><img src='img/heads/tab-atkins.jpg' title='Tab Atkins' /></a>
      <a href='#panellists-andy-hume'><img src='img/heads/andy-hume.jpg' title='Andy Hume' /></a>
    </p>
    <p>Moderator: <a href='#panellists-amber-weinberg'>Amber Weinberg</a></p>
    <div class="watchVideo" data-topic="responsive"><i class='icon-play-circle icon-white'></i> <span>Watch video</span></div>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T15:15:00Z'>15:15</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>5. Input</h3>
      <p>How do we write web apps that are agnostic to different input technologies?  What about devices that combine touch and mouse, and what of new interaction methods like remote controls, speech and 3D gestures? What problems do we encounter when we expand support to encompass embedded browsers in devices like kiosks, TVs, games consoles, in-flight and in-car screens?</p>
      <ul class='spec-links'>
        <li><a href='http://github.com/ftlabs/fastclick'><i class="icon-asterisk icon-white"></i> FT Fastclick</a></li>
        <li><a href='http://smus.com/mouse-touch-pointer/'><i class="icon-asterisk icon-white"></i> pointer.js</a></li>
        <li><a href='http://www.w3.org/Submission/pointer-events/'><i class="icon-asterisk icon-white"></i> Pointer events</a></li>
        <li><a href='http://lists.w3.org/Archives/Public/public-xg-htmlspeech/2011Feb/att-0020/api-draft.html'><i class="icon-asterisk icon-white"></i> Speech input API</a></li>
        <li><a href='http://console.maban.co.uk/'><i class="icon-asterisk icon-white"></i> Console browsers</a></li>
        <li><a href='https://leapmotion.com/'><i class="icon-asterisk icon-white"></i> Leap motion</a></li>
        <li><a href='http://www.headlondon.com/our-thoughts/technology/posts/microsoft-kinect-and-the-web-browser-enter-zigfu'><i class="icon-asterisk icon-white"></i> Kinect hacks</a></li>
      </ul>
    </div>
    <div class='span4 headshots'>
    <p>
      <a href='#panellists-matt-caruana-galizia' class='speaker'><img src='img/heads/matt-cg.jpg' title='Matt Caruana Galizia (opener)' /></a>
      <a href='#panellists-boris-smus'><img src='img/heads/boris-smus.jpg' title='Boris Smus' /></a>
      <a href='#panellists-mairead-buchan'><img src='img/heads/mairead-buchan.jpg' title='Mairead Buchan' /></a>
      <a href='#panellists-francois-daoust'><img src='img/heads/francois-daoust.jpg' title='François Daoust' /></a>
    </p>
    <p>Moderator: <a href='#panellists-pete-lepage'>Pete LePage</a></p>
    <div class="watchVideo" data-topic="input"><i class='icon-play-circle icon-white'></i> <span>Watch video</span></div>
    </div>
  </div>

  <div class='row session session-break'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T16:15:00Z'>16:15</time><i class='ch'></i></div></div>
    <div class='span10'>
      <h3>Break</h3>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T16:30:00Z'>16:30</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>6. Privileged access</h3>
      <p>Slowly, websites have been peeking outside the browser sandbox, though we remain some way off an interoperable solution for the holy grail of a website-as-desktop-app without any runtime other than the browser.  How do we get there more quickly, and in the meantime navigate problems like conflicting and confusing user permission prompts, testing and updating, and do we get the access we actually need?</p>
      <ul class='spec-links'>
        <li><a href='http://developer.chrome.com/trunk/apps/about_apps.html'><i class="icon-asterisk icon-white"></i> Chrome packaged apps</a></li>
        <li><a href='http://phonegap.com/'><i class="icon-asterisk icon-white"></i> Phonegap</a></li>
        <li><a href='http://www.w3.org/TR/widgets/'><i class="icon-asterisk icon-white"></i> W3C Widgets</a></li>
        <li><a href='http://www.w3.org/TR/2011/WD-dap-policy-reqs-20110118/'><i class="icon-asterisk icon-white"></i> Device API access control</a></li>
      </ul>
    </div>
    <div class='span4 headshots'>
    <p>
      <a href='#panellists-brian-leroux' class='speaker'><img src='img/heads/brian-leroux.jpg' title='Brian Leroux (opener)' /></a>
      <a href='#panellists-paul-kinlan'><img src='img/heads/paul-kinlan.jpg' title='Paul Kinlan' /></a>
      <a href='#panellists-petro-soininen'><img src='img/heads/petro-soininen.jpg' title='Petro Soininen' /></a>
      <a href='#panellists-diana-cheng'><img src='img/heads/diana-cheng.jpg' title='Diana Cheng' /></a>
    </p>
    <p>
      Moderator: <a href='#panellists-chris-heilmann'>Chris Heilmann</a>
    </p>
    <div class="watchVideo" data-topic="privileged"><i class='icon-play-circle icon-white'></i> <span>Watch video</span></div>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T17:30:00Z'>17:30</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>7. Testing and tooling</h3>
      <p>Sites have become too complex to build by hand, and too complex to test without automation.  What are the tools we now rely on for authoring and testing?  Where are the gaps?  Where do we need to focus attention to improve support?</p>
      <ul class='spec-links'>
        <li><a href='https://developers.google.com/chrome-developer-tools/docs/overview'><i class="icon-asterisk icon-white"></i> Chrome DevTools</a></li>
        <li><a href='http://html.adobe.com/edge/'><i class="icon-asterisk icon-white"></i> Adobe Edge</a></li>
        <li><a href='http://jenkins-ci.org/'><i class="icon-asterisk icon-white"></i> Jenkins</a></li>
        <li><a href='http://seleniumhq.org/'><i class="icon-asterisk icon-white"></i> Selenium</a></li>
        <li><a href='http://www.testplant.com/products/eggplant/mobile/'><i class="icon-asterisk icon-white"></i> Eggplant</a></li>
        <li><a href='http://www.w3.org/TR/webdriver/'><i class="icon-asterisk icon-white"></i> Webdriver</a></li>
      </ul>
    </div>
    <div class='span4 headshots'>
    <p>
      <a href='#panellists-paul-irish' class='speaker'><img src='img/heads/paul-irish.jpg' title='Paul Irish (opener)' /></a>
      <a href='#panellists-remy-sharp'><img src='img/heads/remy-sharp.jpg' title='Remy Sharp' /></a>
      <a href='#panellists-david-blooman'><img src='img/heads/david-blooman.jpg' title='David Blooman' /></a>
      <a href='#panellists-simon-stewart'><img src='img/heads/simon-stewart.jpg' title='Simon Stewart' /></a>
    </p>
    <p>
      Moderator: <a href='#panellists-ivan-zuzak'>Ivan Žužak</a>
    </p>
    <div class="watchVideo" data-topic="testing"><i class='icon-play-circle icon-white'></i> <span>Watch video</span></div>
    </div>
  </div>

  <div class='row session'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T18:30:00Z'>18:30</time><i class='ch'></i></div></div>
    <div class='span6'>
      <h3>Closing remarks and thanks</h3>
    </div>
    <div class='span4 headshots'></div>
  </div>

  <div class='row session session-break'>
    <div class='span2'><div class='session-time'><time datetime='2013-02-09T18:45:00Z'>18:45</time><i class='ch'></i></div></div>
    <div class='span10'>
      <h3>After party at The Crown</h3>
      <p>After the conference we'll head over to <a href='https://plus.google.com/105925792460632400596/about'>The Crown</a> for drinks.  Dinner is up to you - The Crown serves food, but there are probably thousands of alternatives within walking distance.</p>
    </div>
  </div>
  </div>
  </div>

  <div class='wrap' id='format'>
  <div class='container'>

  <h2>Session format</h2>

  <div class='row'>
    <div class='span8'>
      <p>Edge is a different kind of conference, for developers with <strong>experience to share</strong>, who want to see and bring improvements to the web platform.  Our emphasis is on creating a good environment for productive debate and discussion, rather than presenting the experiences of a single speaker.</p>

      <p>Each themed session is an hour long, and starts with a maximum 10 minute talk by an expert in that topic, outlining the current state of the platform in that area.  Expect this to be a <strong>fast moving and dense blast of information</strong> to get you thinking.  The remainder of the session will be given over to an open but structured discussion, with a professional moderator and a panel of seasoned developers who have in-depth knowledge of the subject.  They’ve been there, done it, and often bring different perspectives on how we can solve problems.</p>

      <p>Session participants will include the <em>lead speaker</em>, a number of additional <em>panellists</em>, a <em>moderator</em>, and a <em>notetaker</em> to record the discussion so we can share it on the web later.</p>

      <p>The session programme at Edge is designed to ensure that the day covers a broad swathe of topics, giving each equal weight.  It's open to anyone, and is designed to be a simple and practical way to connect web developers with browser developers.</p>
    </div>
    <div class='span4 format-fig'>
      <img src='img/panel.png' />
    </div>
  </div>

  </div>
  </div>

  <div class='wrap wrap-blue' id='panellists'>
  <div class='container'>
  <h2>Panellists</h2>

  <p style='margin-bottom: 30px'>The main purpose of Edge panellists is to help promote discussion, not to own it.  They are here as much to learn from you as to share their own experience.</p>

  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-jake-archibald'>
        <img src='img/heads/jake-archibald.jpg' alt='Jake Archibald' />
        <h3>Jake Archibald</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Works with the Chrome team to develop and promote web standards and developer tools. Prior to Google, worked on <a href='http://lanyrd.com/mobile/'>mobile Lanyrd</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-tab-atkins'>
        <img src='img/heads/tab-atkins.jpg' alt='Tab Atkins' />
        <h3>Tab Atkins</h3>
        <p class='affil'>Google, CSS Working Group</p>
        <p class='bio'>Works on the Chrome browser as a Web Standards Hacker. Also a member of the <a href='http://www.w3.org/Style/CSS/'>CSS Working Group</a>, and either a member or contributor to several other working groups in the <a href='http://www.w3.org/'>W3C</a>. </p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-rowan-beentje'>
        <img src='img/heads/rowan-beentje.jpg' alt='Rowan Beentje' />
        <h3>Rowan Beentje</h3>
        <p class='affil'>FT Labs</p>
        <p class='bio'>Lead developer of momentum scrolling library <a href='https://github.com/ftlabs/ftscroller'>FT Scroller</a>, with broad desktop and mobile browser support.  Also works on <a href='http://www.sequelpro.com/'>Sequel Pro</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-andrew-betts'>
        <img src='img/heads/andrew-betts.jpg' alt='Andrew Betts' />
        <h3>Andrew Betts</h3>
        <p class='affil'>FT Labs</p>
        <p class='bio'>Founder of <a href='http://www.assanka.net'>Assanka</a>, now <a href='http://labs.ft.com'>FT Labs</a>, Andrew leads the team that builds the <a href='http://app.ft.com'>FT web app</a> and <a href='http://appworld.blackberry.com/webstore/content/117808/?lang=en'>Economist HTML5 app</a>, and is the curator of Edge.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-david-blooman'>
        <img src='img/heads/david-blooman.jpg' alt='David Blooman' />
        <h3>David Blooman</h3>
        <p class='affil'>BBC News</p>
        <p class='bio'>Works on <a href='http://www.bbc.co.uk/news'>BBC News</a>.  Helping others test <a href='http://mobiletestingfordummies.tumblr.com/post/20056227958/testing'>mobile</a> and beyond.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-mairead-buchan'>
        <img src='img/heads/mairead-buchan.jpg' alt='Mairead Buchan' />
        <h3>Mairead Buchan</h3>
        <p class='affil'>Head</p>
        <p class='bio'>Front-end lead with <a href='http://www.headlondon.com'>Head</a>, expert on <a href='http://www.headlondon.com/our-thoughts/news/posts/responsive-web-design'>responsive builds</a>, experiments with the <a href='http://vimeo.com/43252331'>Kinect</a> to widen the sphere of possible interactions in responsive development.  </p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-razvan-caliman'>
        <img src='img/heads/razvan-caliman.jpg' alt='Razvan Caliman' />
        <h3>Razvan Caliman</h3>
        <p class='affil'>Adobe</p>
        <p class='bio'>Works on <a href='http://html.adobe.com/webstandards/cssregions/'>CSS Regions</a>, <a href='http://html.adobe.com/webstandards/cssexclusions/'>CSS Exclusions</a> and other ways of improving digital publishing on the web.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-john-cleveley'>
        <img src='img/heads/john-cleveley.jpg' alt='John Cleveley' />
        <h3>John Cleveley</h3>
        <p class='affil'>BBC News</p>
        <p class='bio'>Migrating <a href='http://www.bbc.co.uk/news'>BBC News</a> to a <a href='http://blog.responsivenews.co.uk/post/19230899764/colophon'>dynamic platform</a> and building features <a href='http://blog.responsivenews.co.uk/post/18948466399/cutting-the-mustard'>mobile first</a> using responsive design all the the way up to desktop.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-george-crawford'>
        <img src='img/heads/george-crawford.jpg' alt='George Crawford' />
        <h3>George Crawford</h3>
        <p class='affil'>FT Labs</p>
        <p class='bio'>Lead developer of the <a href='http://www.economist.com/digital'>Economist HTML5 project</a>, and maintainer of <a href='https://github.com/ftlabs/ftcolumnflow'>FT Columnflow</a>, a polyfill for complex multi-column layouts.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-andy-davies'>
        <img src='img/heads/andy-davies.jpg' alt='Andy Davies' />
        <h3>Andy Davies</h3>
        <p class='affil'>Freelance performance consultant</p>
        <p class='bio'>Developer of <a href="https://github.com/andydavies/waterfall">waterfall</a> and <a href="https://github.com/andydavies/kensho">kensho</a>.  Works with ecommerce customers to measure and improve site performance. Fascinated by network waterfalls. </p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-jackson-gabbard'>
        <img src='img/heads/jackson-gabbard.png' alt='Jackson Gabbard' />
        <h3>Jackson Gabbard</h3>
        <p class='affil'>Facebook</p>
        <p class='bio'><a href='http://www.facebook.com/jg/posts/702360716968'>Veteran</a> <a href='http://fb.me/jg'>troublemaker</a> <a href='http://en.wikipedia.org/wiki/Walker_(Star_Wars)#All_Terrain_Armored_Transport_.28AT-AT.29'>at</a> <a href='https://www.facebook.com/facebooklondon'>FB London</a>. <a href='https://www.facebook.com/jg/timeline/story?ut=32&amp;wstart=1346482800&amp;wend=1349074799&amp;hash=751012099208&amp;pagefilter=3&amp;ustart=1'>Works</a> <a href='http://www.facebook.com/photo.php?fbid=382575751827880&amp;set=a.344673028951486.80946.265781023507354&amp;type=1&amp;theater'>on</a> <a href='https://www.facebook.com/careers/department?dept=grad&amp;req=a2KA0000000DwaNMAS'>Tools</a> <a href='http://en.wikipedia.org/wiki/Logical_conjunction'>and</a> <a href='https://www.facebook.com/careers/department?dept=grad&amp;req=a2KA0000000DwZjMAK'>Mobile</a>. <a href='https://www.facebook.com/photo.php?fbid=2572123311945&amp;set=a.1096903392369.2013734.1520144806&amp;type=1'>Helped</a> <a href='https://www.facebook.com/photo.php?fbid=10150604077667200&amp;set=a.10150604077432200.408970.9445547199&amp;type=1'>build</a> <a href='https://www.facebook.com/blog/blog.php?post=10150408335607131'>Mobile Timeline</a> <a href='http://en.wikipedia.org/wiki/AND_gate'>and</a> <a href='https://m.facebook.com/appcenter/'>App Center</a>. <a href='http://lanyrd.com/profile/jackson_gabbard/past/'>Speaker</a>, <a href='http://en.wikipedia.org/wiki/Metamucil'>regular</a> <a href='http://edgeconf.com/#panellists'>troll</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-matt-caruana-galizia'>
        <img src='img/heads/matt-cg.jpg' alt='Matt Caruana Galizia' />
        <h3>Matt Caruana Galizia</h3>
        <p class='affil'>FT Labs</p>
        <p class='bio'>Developer on the <a href='http://app.ft.com'>FT Web App</a> and maintainer of <a href='http://github.com/ftlabs/fastclick'>FT Fastclick</a>, a polyfill to increase responsiveness of touch UIs.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-ilya-grigorik'>
        <img src='img/heads/ilya-grigorik.png' alt='Pete LePage' />
        <h3>Ilya Grigorik</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Engineer and developer advocate on the Make The Web Fast team at Google, driving adoption of performance best practices.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-andy-hume'>
        <img src='img/heads/andy-hume.jpg' alt='Andy Hume' />
        <h3>Andy Hume</h3>
        <p class='affil'>The Guardian</p>
        <p class='bio'>Formerly a lead engineer on <a href="http://www.bing.com/maps/">Bing Maps</a>, and developer at <a href="http://clearleft.com">Clearleft</a>.  Currently client-side architect at the <a href="http://www.guardian.co.uk">Guardian</a>.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-paul-irish'>
        <img src='img/heads/paul-irish.jpg' alt='Paul Irish' />
        <h3>Paul Irish</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Known for a plethora of web dev tools including <a href="http://yeoman.io">Yeoman</a>, <a href="http://www.modernizr.com">Modernizr</a>, <a href="http://html5boilerplate.com">HTML5 Boilerplate</a>,  <a href="http://html5please.us">HTML5 Please</a>, <a href="http://www.css3please.com">CSS3 Please</a> and other bits and bobs of open source code.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-paul-kinlan'>
        <img src='img/heads/paul-kinlan.jpg' alt='Paul Kinlan' />
        <h3>Paul Kinlan</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Mr <a href='http://webintents.org'>Web Intents</a>. Developer of many techie things including Twollo, Twe2, Topicala, Ahoyo and FriendDeck.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-tobie-langel'>
        <img src='img/heads/tobie-langel.jpg' alt='Tobie Langel' />
        <h3>Tobie Langel</h3>
        <p class='affil'>Facebook</p>
        <p class='bio'>Focuses on Open Web Standards, and is Facebook's <a href='http://www.w3.org/2005/10/Process-20051014/organization#AC'>W3C AC</a> Rep. An avid open-source contributor, known for co-maintaining <a href='http://prototypejs.org/'>Prototype</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-pete-lepage'>
        <img src='img/heads/pete-lepage.png' alt='Pete LePage' />
        <h3>Pete LePage</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Developer advocate on the Chrome team who helps to make the web a more awesome place for developers.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-jake-archibald'>
        <img src='img/heads/brian-leroux.jpg' alt='Brian Leroux' />
        <h3>Brian Leroux</h3>
        <p class='affil'>Adobe</p>
        <p class='bio'>Formerly of <a href='http://www.crunchbase.com/company/nitobi-software'>Nitobi</a>, works on <a href='http://incubator.apache.org/cordova/'>Cordova</a>, <a href='http://www.phonegap.com'>Phonegap</a>, and other projects that orbit the amazing gravity of JavaScript.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-alex-russell'>
        <img src='img/heads/alex-russell.jpg' alt='Alex Russell' />
        <h3>Alex Russell</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Works on Chrome, <a href='http://www.google.com/intl/en/chrome/browser/mobile/android.html'>Chrome for Android</a>, <a href='https://developers.google.com/chrome/chrome-frame/'>Chrome Frame</a>, and the broader web platform at Google London.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-remy-sharp'>
        <img src='img/heads/remy-sharp.jpg' alt='Remy Sharp' />
        <h3>Remy Sharp</h3>
        <p class='affil'>LeftLogic</p>
        <p class='bio'>Founder and curator of <a href="http://full-frontal.org">Full Frontal</a>. Also ran <a href="http://jqueryfordesigners.com">jQuery for Designers</a>, co-authored <a href="http://introducinghtml5.com">Introducing HTML5</a> (adding all the JavaScripty bits) and is one of the curators of <a href="http://html5doctor.com">HTML5Doctor.com</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-boris-smus'>
        <img src='img/heads/boris-smus.jpg' alt='Boris Smus' />
        <h3>Boris Smus</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Research software engineer prototyping new kinds of input for the web platform.  Creator of <a href='https://github.com/borismus/pointer.js'>pointer.js</a> and <a href='https://github.com/borismus/device.js'>device.js</a>.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-ivan-zuzak'>
        <img src='img/heads/ivan-zuzak.jpg' alt='Ivan Žužak' />
        <h3>Ivan Žužak</h3>
        <p class='affil'>Asseco SEE</p>
        <p class='bio'>Developed the postMessage-based <a href='https://github.com/izuzak/pmrpc'>pmrpc</a> library and other <a href='https://github.com/izuzak'>open-source webeng tools</a>.  Publishes <a href='http://thisweekinrest.wordpress.com'>This Week in REST</a> and maintains a <a href='http://ivanzuzak.info/2012/11/18/the-web-engineers-online-toolbox.html'>list of online tools</a> for Web engineers.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-chris-heilmann'>
        <img src='img/heads/chris-heilmann.jpg' alt='Chris Heilmann' />
        <h3>Chris Heilmann</h3>
        <p class='affil'>Mozilla</p>
        <p class='bio'>Principal Developer Evangelist at Mozilla, author of or contributor to four books and hundreds of articles on web development.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-amber-weinberg'>
        <img src='img/heads/amber-weinberg.jpg' alt='Amber Weinberg' />
        <h3>Amber Weinberg</h3>
        <p class='affil'>Freelance Wordpress Developer</p>
        <p class='bio'>Responsive front end developer, regular <a href='http://www.amberweinberg.com'>blogger</a>, creator of <a href='http://hired.im'>hired.im</a> (supporting <a href='http://www.codeclub.org.uk/'>CodeClub</a>) and dispenser of <a href='http://www.slideshare.net/amberweinberg/20-mobile-ui-tips-for-developers'>mobile UX wisdom</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-petro-soininen'>
        <img src='img/heads/petro-soininen.jpg' alt='Petro Soininen' />
        <h3>Petro Soininen</h3>
        <p class='affil'>SC5</p>
        <p class='bio'>Head of Technology at <a href='http://sc5.io/'>SC5</a> and organizer of <a href='http://finhtml5.fi/'>FINHTML5</a>, with a huge bag of war stories from exposing <a href='http://www.developer.nokia.com/'>Nokia web dev platforms</a> to the world.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-mark-christian'>
        <img src='img/heads/mark-christian.jpg' alt='Mark Christian' />
        <h3>Mark Christian</h3>
        <p class='affil'>Twitter</p>
        <p class='bio'>Canadian bringing whimsy to California. Web infrastructure at Twitter; <a href='https://shinyplasticbag.com/dragondrop/'>DragonDrop</a>, <a href='http://responsivemeasure.com/'>Responsive Measure</a>, <a href='http://ffffallback.com/'>FFFFallback</a> and <a href='http://appcachefacts.info/'>AppcacheFacts</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-steve-thair'>
        <img src='img/heads/steve-thair.jpg' alt='Steve Thair' />
        <h3>Steve Thair</h3>
        <p class='affil'>Seriti Consulting</p>
        <p class='bio'>Web Operations Manager and Performance Consultant.  Organises <a href='http://www.meetup.com/London-Web-Performance-Group/'>London Web Performance</a> and <a href='http://webperfdays.org/'>WebPerfDays</a>.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-diana-cheng'>
        <img src='img/heads/diana-cheng.jpg' alt='Diana Cheng' />
        <h3>Diana Cheng</h3>
        <p class='affil'>Vodafone</p>
        <p class='bio'>Vodafone Group R&amp;D, currently <a href='http://www.w3.org/2009/dap/'>W3C Device APIs</a> and <a href='http://www.w3.org/2012/09/sysapps-wg-charter'>SysApps</a>, particularly <a href='http://www.w3.org/TR/netinfo-api/'>Network Info API</a>.  Previously <a href='http://www.w3.org/2008/geolocation/'>W3C Geolocation WG</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-simon-stewart'>
        <img src='img/heads/simon-stewart.jpg' alt='Simon Stewart' />
        <h3>Simon Stewart</h3>
        <p class='affil'>Facebook</p>
        <p class='bio'>Lead of <a href='http://seleniumhq.org/'>the Selenium project</a>, creator of <a href='http://seleniumhq.org/docs/03_webdriver.jsp'>WebDriver</a>. Currently an engineer at <a href='https://www.facebook.com/facebooklondon'>Facebook</a>, but has previously led Google's <a href='http://googletesting.blogspot.co.uk/'>Web Testing team</a> and remembers his time at <a href='http://www.thoughtworks.com/'>ThoughtWorks</a> fondly.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-chris-lord'>
        <img src='img/heads/chris-lord.jpg' alt='Chris Lord' />
        <h3>Chris Lord</h3>
        <p class='affil'>Mozilla</p>
        <p class='bio'>Mobile platform guy at <a href='http://www.mozilla.org/en-US/about/mozilla-spaces/'>Mozilla's</a> <a href='http://www.mozilla.org/en-US/about/mozilla-spaces/'>London office</a>, and free software advocate. Works primarily on graphics and performance for <a href='https://play.google.com/store/apps/details?id=org.mozilla.firefox'>Firefox on Android</a>.</p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-matt-delaney'>
        <img src='img/heads/matt-delaney.jpg' alt='Matt Delaney' />
        <h3>Matt Delaney</h3>
        <p class='affil'>Konsult</p>
        <p class='bio'>Founder of <a href='http://konsu.lt/'>Konsult</a>, formerly a <a href='http://www.webkit.org'>WebKit</a> engineer at <a href='http://www.apple.com'>Apple</a> focused on graphics, performance, and hardware-accelerated rendering.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-francois-daoust'>
        <img src='img/heads/francois-daoust.jpg' alt='Francois Daoust' />
        <h3>François Daoust</h3>
        <p class='affil'>Joshfire</p>
        <p class='bio'>'Factory worker' at <a href="http://factory.joshfire.com" title="The Joshfire Factory">Joshfire</a>, ex-W3C, enjoys making cross-device apps. Co-author of a <a href="http://lewebmobile.fr" title="Relever le défi du Web mobile">French book on mobile Web</a></p>
      </div>
    </div>
    <div class='span6'>
      <div class='speaker' id='panellists-pavel-feldman'>
        <img src='img/heads/pavel-feldman.jpg' alt='Pavel Feldman' />
        <h3>Pavel Feldman</h3>
        <p class='affil'>Google</p>
        <p class='bio'>Pavel is a software engineer working on Google <a href='https://developers.google.com/chrome-developer-tools/'>Chrome Developer Tools</a> and <a href='http://webkit.org/'>WebKit's</a> Web Inspector.</p>
      </div>
    </div>
  </div>
  <div class='row speakers'>
    <div class='span6'>
      <div class='speaker' id='panellists-shane-osullivan'>
        <img src='img/heads/shane-osullivan.jpg' alt='Shane O&#39;Sullivan' />
        <h3>Shane O'Sullivan</h3>
        <p class='affil'>Facebook</p>
        <p class='bio'><a href='http://shaneosullivan.wordpress.com/'>UI</a> <a href='http://facebook.com/shaneos'>engineer</a> focusing on <a href='https://play.google.com/store/apps/details?id=com.facebook.pages.app'>mobile</a> and desktop framework development for building <a href='http://facebook.com/ads/create'>business interfaces</a> at Facebook.  <a href='http://dojocampus.org/explorer/'>Contributor</a> to the <a href='http://dojotoolkit.org/'>Dojo Toolkit</a>.</p>
      </div>
    </div>
  </div>

  </div>
  </div>

  <div class='wrap' id='venue'>
  <div class='container'>

  <h2>Venue</h2>

  <div class='row'>
    <div class='span3'>
      <p>Edge was held at Facebook's colourful London event space in Covent Garden:</p>

      <address>
      <strong>Facebook London</strong><br>
      42 Earlham Street<br />
      Covent Garden<br />
      London<br />
      WC2H 9LA
     </address>

     <ul class='nearest-tube'>
       <li>Tottenham Court Road</li>
       <li>Covent Garden</li>
     </ul>

     <p><a href="http://www.google.co.uk/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q=wc2h+9la&amp;aq=&amp;sll=47.754098,-4.306641&amp;sspn=41.824571,126.210937&amp;ie=UTF8&amp;hq=&amp;hnear=London+WC2H+9LA,+United+Kingdom&amp;t=m&amp;ll=51.513764,-0.125742&amp;spn=0.025639,0.054932&amp;z=14&amp;iwloc=A">View map on Google Maps</a></p>
    </div>
    <div class='span9'>
      <img src='img/fblondon.jpg' />
    </div>
  </div>

  </div>
  </div>

  <div class='wrap wrap-grey' id='faq'>
  <div class='container'>

  <h2>FAQs</h2>

  <dl class='faq'>
    <dt>Who should attend?</dt>
    <dd>If you are a web developer using technologies that you can only find in pre-release versions of browsers, and you are finding that they don’t quite work as you think they should, we want to see you at Edge.  Or if you have developed intricate workarounds, shims or polyfills to make up for the deficiencies of web standards or differences in implementation between browsers, you should come too.</dd>

    <dt>What does it cost?</dt>
    <dd>Tickets are £50, which is used to pay for speaker travel expenses.  If there is any left over, we will take a vote of attendees and donate it to a nominated open source project.</dd>

    <dt>Who’s paying for everything then?</dt>
    <dd>Edge is supported and funded by the three organisations behind the event: <a href='http://facebook.com/facebooklondon'>Facebook</a>, <a href='http://www.google.com'>Google</a> and the <a href='http://www.ft.com'>Financial Times</a> (via <a href='http://labs.ft.com'>FT Labs</a>).</dd>

    <dt>Why aren't there more women on the panels?</dt>
    <dd>We're very much aware that our panels are male dominated, even more so than the broader tech industry.  Underrepresentation of women in the industry is a problem we fully acknowledge and is something everyone in the community should be working hard to change, the organisers of this event included.  There are many ways of doing this, and it can't be as simple as saying that every event without exception must be representative to have any legitimacy.  If small, niche events go unstaged for fear of unintentionally provoking arguments over diversity, then we're the poorer for the lack of valuable knowledge sharing opportunities that those events would have provided.  As many gains may be made through social initiatives which avoid the need for quotas and other discriminatory ways of <em>creating</em> diversity.</dd>
    <dd>Also, Edge doesn't have any speakers in the traditional sense.  Our panellists are not there to teach or lecture you, they're there to help and to learn from the most important people at the event - our delegates.  Representation of women in our delegate list is roughly in line with the industry average.</dd>

    <dt>How did you choose your panellists?</dt>
    <dd>About half the panellists are employees of the three conference organisers.  They are paid to attend Edge, represent their company, and participate in the discussion as part of their job.  They were nominated by their respective employers.  The remainder come from exploring the networks of existing panellists, reaching out to spec authors, and asking relevant industry leading organisations for representatives or recommendations.</dd>
  </dl>
  </div>
  </div>

  <div class='wrap wrap-black' id='footer'>
  <div class='container'>

  <footer>
    <p class="pull-right"><a href="#">Back to top</a></p>
    <p>&copy; 2012 Edge contributors &middot; This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/deed.en_GB">Creative Commons License</a>.</p>
  </footer>

</div><!-- /.container -->
</div>

<div id="signup" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-body">
    <iframe id='ifrsignup' src="https://docs.google.com/a/ft.com/spreadsheet/embeddedform?formkey=dHJmaV9rUGJDSnBkU0l0NlBQZmUyZWc6MQ" frameborder="0" marginheight="0" marginwidth="0">Loading...</iframe>
  </div>
</div>

  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
  <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>

  <script src="js/videos.js"></script>
  <script src="js/videosearch.js"></script>
  <script src="js/lib/callPlayer.js"></script>

  <script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-36962287-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
  </body>
</html>
