var timers = {};

function getCOOKIE(name) {
	var parts = document.cookie.split(name + '=');
	if(parts.length === 2) return parts.pop().split(";").shift();
}
function expireCOOKIE(name) {
	$.ajax({
		url: '/expireCOOKIE',
		method: 'POST',
		data: {name: name}
	});
}
function blockLINK($link, extra, oncomplete) {
	var $dialog = bootbox.dialog({ closeButton: false, message:
		'<div class="step">COLLECTING INFORMATION <span class="jumping-dots"><span>.</span><span>.</span><span>.</span></span></div>'
		+'<div class="progress" style="display: none;">'
				+'<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;">0%</div>'
		+'</div>'});
	
	var t = $link.attr('t'),
	tv = $link.attr('tv');

	getPROGRESS($link, $dialog);

	var timer = 0;
	var finished = false;
	timers[t] = window.setInterval(function() {
		var token = getCOOKIE(t);
		
		if(token === tv) {
			unblockLINK($link, $dialog);
			
			finished = true;
			setPROGRESS($dialog, 100);
		}
		
		// get progress every 2.5s
		if(finished === false && timer > 2500) {
			timer -= 2500;
			getPROGRESS($link, $dialog);
		}
		
		timer += 1000;
	}, 1000);
}
function unblockLINK($link, $dialog) {
	var t = $link.attr('t');
	$dialog.modal('hide');
	
	try {
		expireCOOKIE(t);
		setTimeout(function() {window.clearInterval(timers[t]);}, 2500);
	} catch(e) { /* failed to clear timer */ }
}
function setPROGRESS($dialog, progress) {
	$dialog.find('.step').html('PROCESSING');
	$dialog.find('.progress').show();
	$dialog.find('.progress').find('.progress-bar').attr('aria-valuenow', progress);
	$dialog.find('.progress').find('.progress-bar').css({width: progress + '%'});
	$dialog.find('.progress').find('.progress-bar').html(progress + '%');
}
function getPROGRESS($link, $dialog) {
	$.ajax({
		url: $link.attr('progress-url'),
		method: 'GET',
		success: function(progress) {
			if(progress) {
				setPROGRESS($dialog, progress);
			}
		}
	});
}
	
$(document).ready(function() {
	$('.fsearch form').submit(function(event) {
		event.preventDefault();
		
		var $form = $(this),
		$submit = $form.find('.fsearchbtn');
		
		$('.fvideos').empty().hide();
		
		// start loading
		$submit.prop('disabled', true);
		$submit.find('i').hide();
		$submit.find('i.fsearchldr').show();
		
		$.ajax({
			url: $form.attr('action'),
			method: $form.attr('method'),
			data: $form.serialize(),
			success: function(i) {
				$('.ferrors').hide();
				$('.ferrors ul').empty();
				
				if(i.passes) {
					$('.fvideos').html(i.html).show();
					
					$('.fvideos').find('[data-toggle="tooltip"]').tooltip({
						container: 'body',
						placement: 'top'
					});
					
					$('.fvideos').find('.download').click(function(event) {
						blockLINK($(this));
					});
					$('.fvideos').find('.download-all').click(function(event) {
						if($('.fvideos').find('.video').find('[type=checkbox]:checked').length) {
							$('.ferrors').hide();
							$('.ferrors ul').empty();
							blockLINK($(this));

							var href = $(this).attr('href');
							var videos = [];
							$('.fvideos').find('.video').find('[type=checkbox]:checked').each(function() {
								videos.push($(this).attr('name') + '=' + $(this).prop('value'));
							});

							window.location.href = href + $(this).attr('separator') + videos.join('&');
						}
						else {
							$('.ferrors').show();
							$('.ferrors ul').html("<li>You didn't select any video.</li>");
						}
					});
					
					$('.fvideos').find('.video-list-header span[data-target=checkbox]').click(function(event) {
						var $checkbox = $('#' + $(this).attr('for'));
						$checkbox.prop('checked', !$checkbox.prop('checked'));
						
						if($checkbox.is(':checked')) {
							$('.fvideos').find('.video').find('[type=checkbox]').prop('checked', true);
						}
						else {
							$('.fvideos').find('.video').find('[type=checkbox]').prop('checked', false);
						}
					});
					
					$('.fvideos').find('.video span[data-target=checkbox]').click(function(event) {
						var $checkbox = $('#' + $(this).attr('for'));
						$checkbox.prop('checked', !$checkbox.prop('checked'));
						
						if($checkbox.is(':checked')) {
							$('.fvideos').find('.video-list-header [type=checkbox]').prop('checked', true);
						}
						else {
							if($('.fvideos').find('.video [type=checkbox]:checked').length === 0) {
								$('.fvideos').find('.video-list-header [type=checkbox]').prop('checked', false);
							}
						}
					});
				}
				else {
					$('.ferrors').show();
					$.each(i.errors, function(i, v) {
						$('.ferrors ul').html('<li>' + v + '</li>');
					});
				}
			},
			error: function(i) {
				$('.ferrors').show();
				$('.ferrors ul').html('<li>Whoops! An error occurred.</li>');
			},
			complete: function(i) {
				$submit.prop('disabled', false);
				$submit.find('i').show();
				$submit.find('i.fsearchldr').hide();
			}
		});
	});
	
	$('.fsearch form').submit();
});