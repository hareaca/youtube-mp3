<div class="video">
	<div class="image">
		<img src="http://i1.ytimg.com/vi/{!! $URLID !!}/default.jpg">
	</div>
	<div class="details">
		<div><b>Title:</b> {!! $videoINFO->title !!}</div>
		<div>
			<?php
				$__t_ = '__t_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
				$__tv_ = '__tv_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
				$__i_ = '__i_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
			?>
			<a href="{!! route('download', [$URLID, 't' => $__t_, 'tv' => $__tv_, 'i' => $__i_]) !!}" class="download" downloading-html="<i class='fa fa-save'></i> downloading <span class='jumping-dots'><span>.</span><span>.</span><span>.</span></span>"
				 t="{!! $__t_ !!}"
				 tv="{!! $__tv_ !!}"
				 progress-url="{!! route('getPROGRESS', [$__i_]) !!}">
				<i class="fa fa-save"></i> download mp3
			</a>
		</div>
	</div>
	<div class="clear"></div>
</div>