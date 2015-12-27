@if(count($videoLISTARRAY) > 1)
<div class="vide-list">
	<div class="video-list-header">
		<?php
			$__t_ = '__t_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
			$__tv_ = '__tv_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
			$__i_ = '__i_'.substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 15);
		?>
		<span class="btn btn-default" data-target="checkbox" for="select-all">
			{!! Form::checkbox(null, null, true, ['id' => 'select-all']) !!}
			<span>select all</span>
		</span>
		<span href="{!! route('downloadlist', ['t' => $__t_, 'tv' => $__tv_, 'i' => $__i_]) !!}" separator="&" class="btn btn-default download-all" downloading-html="downloading<span class='pprogress'></span> <span class='jumping-dots'><span>.</span><span>.</span><span>.</span></span>"
			 t="{!! $__t_ !!}"
			 tv="{!! $__tv_ !!}"
			 progress-url="{!! route('getPROGRESS', [$__i_]) !!}">
			<i class="fa fa-download"></i> download all selected
		</span>
	</div>
<?php $i = 0; ?>
@foreach($videoLISTARRAY as $URLID => $videoINFO)
	@include('video', ['URLID' => $URLID, 'videoINFO' => $videoINFO, 'list' => true, 'i' => ++$i])
@endforeach
</div>
@else
	@foreach($videoLISTARRAY as $URLID => $videoINFO)
		@include('video', ['URLID' => $URLID, 'videoINFO' => $videoINFO])
	@endforeach
@endif