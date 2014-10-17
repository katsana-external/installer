<?php

$authFormGroup = function ($authentication, $auth) {
	$class = ['form-group'];
	(false === $authentication) && $class[] = 'error';
	('eloquent' !== $auth['driver']) && $class[] = 'hide';

	return ['class' => implode(' ', $class)];
}; ?>

<div class="row">
	<div class="twelve columns">
		<h3>{{ trans('orchestra/foundation::install.auth.title') }}</h3>

		<p>
			{!! trans('orchestra/foundation::install.verify', [
				'filename' => app('html')->create('code', 'app/config/auth.php', ['title' => app_path('config/auth.php')])
			]) !!}
		</p>

		<div class="form-group">
			<label class="three columns control-label {!! 'fluent' === $auth['driver'] ? 'error' : '' !!}">
				{{ trans('orchestra/foundation::install.auth.driver') }}
			</label>
			<div class="nine columns">
				<input disabled class="form-control" type="text" value="{!! $auth['driver'] !!}">
				@if ('fluent' === $auth['driver'])
				<p class="help-block">{{ trans('orchestra/foundation::install.auth.requirement.driver') }}</p>
				@endif
			</div>
		</div>

		<div{!! app('html')->attributes($authFormGroup($authentication, $auth)) !!}>
			<label class="three columns control-label">
				{{ trans('orchestra/foundation::install.auth.model') }}
			</label>
			<div class="nine columns">
				<input disabled class="form-control" type="text" value="{!! $auth['model'] !!}">
				@if (false === $authentication)
				<p class="help-block">
					{{ trans('orchestra/foundation::install.auth.requirement.driver', [
						'class' => app('html')->create('code', 'Orchestra\Model\User')
					]) }}
				</p>
				@endif
			</div>
		</div>

		@if ($installable)
		<hr>
		<div class="form-group">
			<div class="nine columns offset-by-three">
				<a href="{!! handles('orchestra::install/prepare') !!}" class="btn btn-primary">
					{{ trans('orchestra/foundation::label.next') }}
				</a>
			</div>
		</div>
		@endif
	</div>
</div>
