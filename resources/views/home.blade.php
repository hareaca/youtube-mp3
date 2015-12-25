<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>YouTube to mp3 converter</title>
    
    <!--CSS-->
    {!! Html::style('packages/bootstrap/css/bootstrap.min.css') !!}
    {!! Html::style('packages/font-awesome/css/font-awesome.min.css') !!}
    {!! Html::style('css/style.css') !!}
    <!--/CSS-->
    
    <!--JavaScript-->
    {!! Html::script('packages/jquery/js/jquery-1.11.3.min.js') !!}
    {!! Html::script('packages/bootstrap/js/bootstrap.min.js') !!}
		{!! Html::script('packages/bootstrap/js/bootbox.min.js') !!}
    {!! Html::script('js/script.js') !!}
    <!--/JavaScript-->

  </head>
  <body>
    <div class="container">
			<div class="ttl"><a href="{!! route('home') !!}"><span class="yl"></span> to mp3 converter</a></div>
			<div class="pb">
				<div class="ferrors">
					<div class="alert alert-danger"><ul class="list-unstyled"></ul></div>
				</div>
				<div class="fsearch">
					{!! Form::open(['route' => ['fsearch'], 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
					<div class="input-group">
						{!! Form::text('url', \Request::get('v') ? 'https://www.youtube.com/watch?v='.\Request::get('v') : 'https://www.youtube.com/watch?v=UQ92eyxnxmQ', ['class' => 'form-control']) !!}
						<span class="input-group-btn">
							<button class="btn btn-default fsearchbtn">
								<i class="fa fa-search"></i>
								<i class="fa fa-circle-o-notch fa-spin fsearchldr"></i>
							</button>
						</span>
					</div>
					{!! Form::close() !!}
				</div>
				<div class="fvideos"></div>
			</div>
		</div>
  </body>
</html>