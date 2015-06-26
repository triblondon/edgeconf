/* global $, EventSource */

'use strict';

var evtSource = new EventSource("/q", { withCredentials: false });
var disconnectedBeats = 1;
var alertTimer;

evtSource.addEventListener("add", function(e) {
	console.log(e.id, 'add', e.data);
	var spk = JSON.parse(e.data);
	if ($('#speaker-'+spk.id).length === 0) {
		$('.speaker-queue').append("<div class='small-12 medium-6 columns' id='speaker-"+htmlEncode(spk.id)+"'><div class='queued-speaker'><img src='https://secure.gravatar.com/avatar/"+htmlEncode(spk.gravatar_hash)+"?d=mm&amp;s=50' /><h4>"+htmlEncode(spk.given_name)+" "+htmlEncode(spk.family_name)+"</h4><p>"+htmlEncode(spk.org)+"</p></div></div>").slideDown();
	}
}, false);
evtSource.addEventListener("remove", function(e) {
	console.log(e.id, 'remove', e.data);
	var spk = JSON.parse(e.data);
	$('#speaker-'+spk.id).slideUp(function() {
		$(this).remove();
	});
}, false);

setInterval(function() {
	if (evtSource.readyState === EventSource.OPEN) {
		if (disconnectedBeats) {
			alertMsg('Connected!', 2000);
			disconnectedBeats = 0;
		}
	} else if (disconnectedBeats > 5) {
		location.reload();
	} else {
		$('#alertbar').html('Connecting').addClass('visible');
		disconnectedBeats++;
	}
}, 1000);

function htmlEncode(value){
  //create a in-memory div, set it's inner text(which jQuery automatically encodes)
  //then grab the encoded contents back out.  The div never exists on the page.
  return $('<div/>').text(value).html();
}

function alertMsg(msg, timeout) {
	clearTimeout(alertTimer);
	$('#alertbar').html(msg).addClass('visible');
	if (timeout) {
		alertTimer = setTimeout(function() {
			$('#alertbar').removeClass('visible');
		}, timeout);
	}
}
