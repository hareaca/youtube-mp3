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
function blockLINK($link, extra) {
	$link.data('html', $link.html());
	$link.html($link.attr('downloading-html'));
	$link.attr('disabled', true);
	
	var $dialog = bootbox.dialog({ closeButton: false, message: 'DOWNLOADING<span class="pprogress"></span> <span class="jumping-dots"><span>.</span><span>.</span><span>.</span></span>' });
	
	var t = $link.attr('t'),
	tv = $link.attr('tv');

	try {
		extra($link, $dialog, false);
	} catch(e) { /* extra function can't be executed */ }

	var timer = 0;
	timers[t] = window.setInterval(function() {
		var token = getCOOKIE(t);
		
		if(token === tv) {
			unblockLINK($link, $dialog);
		}
		
		try {
			if(timer > 3500) {
				timer -= 3500;
				extra($link, $dialog);
			}
		} catch(e) { /* extra function can't be executed */ }
		
		timer += 1000;
	}, 1000);
}
function unblockLINK($link, $dialog) {
	var t = $link.attr('t');
	
	$link.attr('disabled', false);
	$link.html($link.data('html'));
	$link.data('html', null);
	
	$dialog.modal('hide');
	
	try {
		expireCOOKIE(t);
		setTimeout(function() {window.clearInterval(timers[t]);}, 2500);
	} catch(e) { /* failed to clear timer */ }
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
						if($('.fvideos').find('.video-box').not('.select-all').find('[type=checkbox]:checked').length) {
							$('.ferrors').hide();
							$('.ferrors ul').empty();
							blockLINK($(this), function($link, $dialog) {
								$.ajax({
									url: $link.attr('progress-url'),
									method: 'GET',
									success: function(progress) {
										if(progress) {
											$dialog.find('.pprogress').html('(' + progress + ')');
											$link.find('.pprogress').html('(' + progress + ')');
										}
									}
								});
							});

							var href = $(this).attr('href');
							var videos = [];
							$('.fvideos').find('.video-box').not('.select-all').find('[type=checkbox]:checked').each(function() {
								videos.push($(this).attr('name') + '=' + $(this).prop('value'));
							});

							window.location.href = href + $(this).attr('separator') + videos.join('&');
						}
						else {
							$('.ferrors').show();
							$('.ferrors ul').html('<li>You didn\'t select any video.</li>');
						}
					});
					
					$('.fvideos').find('.select-all [type=checkbox]').click(function(event) {
						if($(this).is(':checked')) {
							$('.fvideos').find('.video-box').not('.select-all').find('[type=checkbox]').prop('checked', true);
						}
						else {
							$('.fvideos').find('.video-box').not('.select-all').find('[type=checkbox]').prop('checked', false);
						}
					});
					
					$('.fvideos').find('.video-box').not('.select-all').find('[type=checkbox]').click(function(event) {
						if($(this).is(':checked')) {
							$('.fvideos').find('.select-all [type=checkbox]').prop('checked', true);
						}
						else {
							if($('.fvideos').find('.video-box').not('.select-all').find('[type=checkbox]:checked').length === 0) {
								$('.fvideos').find('.select-all [type=checkbox]').prop('checked', false);
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
	
	$(document).mouseup(function (event) {
		var $videos = $('.fvideos').find('.video-box').find('.video');
		if(($videos.is(event.target) === false && $videos.has(event.target).length === 0) === false
					&& $videos.find('.download').is(event.target) === false && $videos.find('.download').has(event.target).length === 0) {
			var $checkbox = $(event.target).closest('.video-box').find('[type=checkbox]');
			$checkbox.prop('checked', !$checkbox.prop('checked'));
			
			if($checkbox.is(':checked')) {
				$('.fvideos').find('.select-all [type=checkbox]').prop('checked', true);
			}
			else {
				if($('.fvideos').find('.video-box').not('.select-all').find('[type=checkbox]:checked').length === 0) {
					$('.fvideos').find('.select-all [type=checkbox]').prop('checked', false);
				}
			}
		}
	});
	
	$('.fsearch form').submit();
});