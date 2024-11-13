<?php

namespace Orchestra\Installation;

use Illuminate\Database\Events\MigrationsStarted;
use Orchestra\Contracts\Installation\Installation as InstallationContract;
use Orchestra\Contracts\Installation\Requirement as RequirementContract;
use Orchestra\Foundation\Support\Providers\ModuleServiceProvider;

class InstallerServiceProvider extends ModuleServiceProvider
{
    /**
     * The application or extension namespace.
     *
     * @var string|null
     */
    protected $namespace = 'Orchestra\Installation\Http\Controllers';

    /**
     * Redirect path after installation completed.
     *
     * @var string
     */
    protected $redirectAfterInstalled;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $path = \realpath(__DIR__ . '/../');
        $this->mergeConfigFrom("{$path}/config/config.php", 'orchestra.installer');

        $this->app->singleton(InstallationContract::class, static function () {
            return new Installation();
        });

        $this->app->singleton(RequirementContract::class, function () {
            $requirement = new Requirement();

            $this->addDefaultSpecifications($requirement);

            return $requirement;
        });

        $this->registerRedirection();

        if ($this->app->runningInConsole() === true) {
            $this->commands([
                Console\InstallCommand::class,
            ]);
        }
    }

    /**
     * Get the events and handlers.
     */
    public function listens(): array
    {
        return [
            MigrationsStarted::class => [function () {
                $this->app->make(InstallationContract::class)->bootInstallerFiles();
            }],
        ];
    }

    /**
     * Register redirection services.
     */
    protected function registerRedirection(): void
    {
        if (! empty($this->redirectAfterInstalled) && \is_string($this->redirectAfterInstalled)) {
            Installation::$redirectAfterInstalled = $this->redirectAfterInstalled;
        }
    }

    /**
     * Add default specifications.
     *
     * @return \Orchestra\Contracts\Installation\Requirement
     */
    protected function addDefaultSpecifications(RequirementContract $requirement)
    {
        return $requirement->add(new Specifications\WritableStorage($this->app))
                ->add(new Specifications\WritableBootstrapCache($this->app))
                ->add(new Specifications\WritableAsset($this->app))
                ->add(new Specifications\DatabaseConnection($this->app))
                ->add(new Specifications\Authentication($this->app));
    }

    /**
     * Boot extension components.
     */
    public function bootExtensionComponents(): void
    {
        $path = \realpath(__DIR__ . '/../');

        $this->publishes([
            "{$path}/config/config.php" => config_path('orchestra/installer.php'),
        ], ['orchestra-installer', 'laravel-config']);

        $this->loadTranslationsFrom("{$path}/resources/lang", 'orchestra/installer');
        $this->loadViewsFrom("{$path}/resources/views", 'orchestra/installer');
    }

    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $path = \realpath(__DIR__ . '/../');

        $this->mergeConfigFrom("{$path}/config/config.php", 'orchestra.installer');

        parent::boot();
    }

    /**
     * Load extension routes.
     */
    protected function loadRoutes(): void
    {
        $path = \realpath(__DIR__ . '/../');

        $this->loadBackendRoutesFrom("{$path}/routes/web.php");
    }
}
