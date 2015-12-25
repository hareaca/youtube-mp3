@if(count($videoLISTARRAY) > 1)
<div class="vide-list">
	<div class="video-box select-all">
		{!! Form::checkbox(null, null, true) !!}
		<div>
			<?php
				$__t_ = '__t_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
				$__tv_ = '__tv_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
				$__i_ = '__i_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
			?>
			<span href="{!! route('downloadlist', ['t' => $__t_, 'tv' => $__tv_, 'i' => $__i_]) !!}" separator="&" class="download-all text-uppercase" downloading-html="downloading<span class='pprogress'></span> <span class='jumping-dots'><span>.</span><span>.</span><span>.</span></span>"
				 t="{!! $__t_ !!}"
				 tv="{!! $__tv_ !!}"
				 progress-url="{!! route('getPROGRESS', [$__i_]) !!}">
				download selected videos
			</span>
			<div class="clear"></div>
		</div>
	</div>
@foreach($videoLISTARRAY as $URLID => $videoINFO)
	<div class="video-box">
		{!! Form::checkbox('__v_[]', $URLID, true) !!}
		@include('video', ['URLID' => $URLID, 'videoINFO' => $videoINFO])
	</div>
@endforeach
</div>
@else
	@foreach($videoLISTARRAY as $URLID => $videoINFO)
		@include('video', ['URLID' => $URLID, 'videoINFO' => $videoINFO])
	@endforeach
@endif