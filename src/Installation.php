<?php namespace Orchestra\Installation;

use Exception;
use Orchestra\Model\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Validation\ValidationException;
use Orchestra\Contracts\Installation\Installation as InstallationContract;

class Installation implements InstallationContract
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Construct a new instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application   $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Boot installer files.
     *
     * @return void
     */
    public function bootInstallerFiles()
    {
        $paths = ['path.database', 'path'];
        $files = $this->app->make('files');

        foreach ($paths as $path) {
            $file = rtrim($this->app[$path], '/').'/orchestra/installer.php';

            if ($files->exists($file)) {
                $files->requireOnce($file);
            }
        }
    }

    /**
     * Migrate Orchestra Platform schema.
     *
     * @return bool
     */
    public function migrate()
    {
        $this->app->make('orchestra.publisher.migrate')->foundation();
        $this->app->make('events')->fire('orchestra.install.schema');

        return true;
    }

    /**
     * Create adminstrator account.
     *
     * @param  array  $input
     * @param  bool   $multiple
     *
     * @return bool
     *
     * @deprecated v3.2.x
     */
    public function createAdmin($input, $multiple = true)
    {
        return $this->make($input, $multiple);
    }

    /**
     * Create adminstrator account.
     *
     * @param  array  $input
     * @param  bool   $multiple
     *
     * @return bool
     */
    public function make(array $input, $multiple = true)
    {
        $messages = $this->app->make('orchestra.messages');

        try {
            $this->validate($input);
        } catch (ValidationException $e) {
            $this->app->make('session')->flash('errors', $e->validator->messages());
            return false;
        }

        try {
            ! $multiple && $this->hasNoExistingUser();

            $this->create($this->createUser($input), $input);

            // Installation is successful, we should be able to generate
            // success message to notify the user. Installer route will be
            // disabled after this point.
            $messages->add('success', trans('orchestra/foundation::install.user.created'));

            return true;
        } catch (Exception $e) {
            $messages->add('error', $e->getMessage());

            return false;
        }
    }

    /**
     * Run application setup.
     *
     * @param  \Orchestra\Model\User  $user
     * @param  array  $input
     *
     * @return void
     */
    public function create(User $user, array $input)
    {
        $config = $this->app->make('config');
        $memory = $this->app->make('orchestra.memory')->make();

        // Bootstrap auth services, so we can use orchestra/auth package
        // configuration.
        $actions = ['Manage Orchestra', 'Manage Users'];
        $admin   = $config->get('orchestra/foundation::roles.admin', 1);
        $roles   = $this->app->make('orchestra.role')->newQuery()->pluck('name', 'id');
        $theme   = [
            'frontend' => 'default',
            'backend'  => 'default',
        ];

        // Attach Administrator role to the newly created administrator.
        $user->roles()->sync([$admin]);

        // Add some basic configuration for Orchestra Platform, including
        // email configuration.
        $memory->put('site.name', $input['site_name']);
        $memory->put('site.theme', $theme);
        $memory->put('email', $config->get('mail'));
        $memory->put('email.from', [
            'name'    => $input['site_name'],
            'address' => $input['email'],
        ]);

        if ($roles instanceof Arrayable) {
            $roles = $roles->toArray();
        }

        // We should also create a basic ACL for Orchestra Platform, since
        // the basic roles is create using Fluent Query Builder we need
        // to manually insert the roles.
        $acl = $this->app->make('orchestra.acl')->make('orchestra');

        $acl->attach($memory);
        $acl->actions()->attach($actions);
        $acl->roles()->attach(array_values($roles));
        $acl->allow($roles[$admin], $actions);

        $this->app->make('events')->fire('orchestra.install: acl', [$acl]);
    }

    /**
     * Validate request.
     *
     * @param  array  $input
     * @return bool
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(array $input)
    {
        // Grab input fields and define the rules for user validations.
        $rules = [
            'email'     => ['required', 'email'],
            'password'  => ['required'],
            'fullname'  => ['required'],
            'site_name' => ['required'],
        ];

        $validation = $this->app->make('validator')->make($input, $rules);

        // Validate user registration, we should stop this process if
        // the user not properly formatted.
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        return true;
    }

    /**
     * Create user account.
     *
     * @param  array  $input
     *
     * @return \Orchestra\Model\User
     */
    public function createUser(array $input)
    {
        User::unguard();

        $user = $this->app->make('orchestra.user')->newInstance();

        $user->fill([
            'email'    => $input['email'],
            'password' => $input['password'],
            'fullname' => $input['fullname'],
            'status'   => User::VERIFIED,
        ]);

        $this->app->make('events')->fire('orchestra.install: user', [$user, $input]);

        $user->save();

        return $user;
    }

    /**
     * Check for existing User.
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function hasNoExistingUser()
    {
        $users = $this->app->make('orchestra.user')->newQuery()->all();

        // Before we create administrator, we should ensure that users table
        // is empty to avoid any possible hijack or invalid request.
        if (empty($users)) {
            return true;
        }

        throw new Exception(trans('orchestra/foundation::install.user.duplicate'));
    }
}
