/* global $, EventSource */

'use strict';

$(function() {
	var evtSource = new EventSource("/q");
	evtSource.addEventListener("add", function(e) {
		console.log(e.id, 'add', e.data);
		var spk = JSON.parse(e.data);
		if ($('#speaker-'+spk.id).length === 0) {
			$('.speaker-queue').append("<div class='queued-speaker' id='speaker-"+htmlEncode(spk.id)+"'><img src='https://secure.gravatar.com/avatar/"+htmlEncode(spk.gravatar_hash)+"?d=mm&amp;s=50' /><h4>"+htmlEncode(spk.given_name)+" "+htmlEncode(spk.family_name)+"</h4><p>"+htmlEncode(spk.org)+"</p></div>").slideDown();
		}
	}, false);
	evtSource.addEventListener("remove", function(e) {
		console.log(e.id, 'remove', e.data);
		var spk = JSON.parse(e.data);
		$('#speaker-'+spk.id).slideUp(function() {
			$(this).remove();
		});
	}, false);
});


function htmlEncode(value){
  //create a in-memory div, set it's inner text(which jQuery automatically encodes)
  //then grab the encoded contents back out.  The div never exists on the page.
  return $('<div/>').text(value).html();
}
