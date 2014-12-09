{!! app('form')->open(['url' => handles('orchestra::install/create'), 'method' => 'POST', 'class' => 'form-horizontal']) !!}

<fieldset>
	<div class="page-header">
		<h3>{{ trans('orchestra/foundation::install.steps.account') }}</h3>
	</div>
	<div class="form-group{{ $errors->has('email') ? ' error' : '' }}">
		{!! app('form')->label('email', trans('orchestra/foundation::label.users.email'), ['class' => 'three columns control-label']) !!}
		<div class="nine columns">
			{!! app('form')->input('email', 'email', '', ['required' => true, 'class' => 'form-control']) !!}
			{!! $errors->first('email', '<p class="help-block">:message</p>') !!}
		</div>
	</div>
	<div class="form-group{{ $errors->has('password') ? ' error' : '' }}">
		{!! app('form')->label('password', trans('orchestra/foundation::label.users.password'), ['class' => 'three columns control-label']) !!}
		<div class="nine columns">
			{!! app('form')->input('password', 'password', '', ['required' => true, 'class' => 'form-control']) !!}
			{!! $errors->first('password', '<p class="help-block">:message</p>') !!}
		</div>
	</div>
	<div class="form-group{!! $errors->has('fullname') ? ' error' : '' !!}">
		{!! app('form')->label('fullname', trans('orchestra/foundation::label.users.fullname'), ['class' => 'three columns control-label']) !!}
		<div class="nine columns">
			{!! app('form')->input('text', 'fullname', 'Administrator', ['required' => true, 'class' => 'form-control']) !!}
			{!! $errors->first('fullname', '<p class="help-block">:message</p>') !!}
		</div>
	</div>
</fieldset>
<fieldset>
	<div class="page-header">
		<h3>{{ trans('orchestra/foundation::install.steps.application') }}</h3>
	</div>
	<div class="form-group{{ $errors->has('site_name') ? ' error' : '' }}">
		{!! app('form')->label('site_name', trans('orchestra/foundation::label.name'), ['class' => 'three columns control-label']) !!}
		<div class="nine columns">
			{!! app('form')->input('text', 'site_name', $siteName, ['required' => true, 'class' => 'form-control']) !!}
			{!! $errors->first('site_name', '<p class="help-block">:message</p>') !!}
		</div>
	</div>
	<div class="row">
		<div class="nine columns offset-by-three">
			<button type="submit" class="btn btn-primary">
				{{ trans('orchestra/foundation::label.submit') }}
			</button>
		</div>
	</div>
</fieldset>

{!! app('form')->close() !!}
