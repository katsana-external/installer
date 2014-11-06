<?php namespace Orchestra\Installation\TestCase;

use Mockery as m;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Orchestra\Installation\Installation;

class InstallationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app = null;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $this->app = new Container;
        $this->app['translator'] = $translator = m::mock('\Illuminate\Translation\Translator')->makePartial();

        $translator->shouldReceive('trans')->andReturn('foo');

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        Container::setInstance($this->app);
    }

    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        unset($this->app);
        m::close();
    }

    /**
     * Get User input.
     *
     * @access private
     * @return array
     */
    private function getUserInput()
    {
        return array(
            'site_name' => 'Orchestra Platform',
            'email'     => 'admin@orchestraplatform.com',
            'password'  => '123456',
            'fullname'  => 'Administrator',
        );
    }

    /**
     * Get validation rules.
     *
     * @access private
     * @return array
     */
    private function getValidationRules()
    {
        return array(
            'email'     => array('required', 'email'),
            'password'  => array('required'),
            'fullname'  => array('required'),
            'site_name' => array('required'),
        );
    }

    /**
     * Test Orchestra\Foundation\Installation\Installation::bootInstallerFiles() method.
     *
     * @test
     */
    public function testBootInstallerFilesMethod()
    {
        $app = $this->app;
        $this->app['path'] = '/var/laravel/app';
        $this->app['path.database'] = '/var/laravel/database';
        $app['files'] = $files = m::mock('\Illuminate\Filesystem\Filesystem')->makePartial();

        $files->shouldReceive('exists')->once()->with('/var/laravel/database/orchestra/installer.php')->andReturn(true)
            ->shouldReceive('requireOnce')->once()->with('/var/laravel/database/orchestra/installer.php')->andReturnNull()
            ->shouldReceive('exists')->once()->with('/var/laravel/app/orchestra/installer.php')->andReturn(true)
            ->shouldReceive('requireOnce')->once()->with('/var/laravel/app/orchestra/installer.php')->andReturnNull();

        $stub = new Installation($app);
        $this->assertNull($stub->bootInstallerFiles());
    }

    /**
     * Test Orchestra\Foundation\Installation\Installation::migrate() method.
     *
     * @test
     */
    public function testMigrateMethod()
    {
        $app = $this->app;
        $app['files'] = m::mock('\Illuminate\Filesystem\Filesystem');
        $app['orchestra.publisher.migrate'] = $migrate = m::mock('\Orchestra\Extension\Publisher\MigrateManager')->makePartial();
        $app['events'] = $events = m::mock('\Illuminate\Events\Dispatcher')->makePartial();

        $migrate->shouldReceive('foundation')->once()->andReturnNull();
        $events->shouldReceive('fire')->once()->with('orchestra.install.schema')->andReturnNull();

        $stub = new Installation($app);
        $this->assertTrue($stub->migrate());
    }
    /**
     * Test Orchestra\Foundation\Installation\Installation::createAdmin() method.
     *
     * @test
     */
    public function testCreateAdminMethod()
    {
        $app = $this->app;
        $app['files'] = m::mock('\Illuminate\Filesystem\Filesystem');
        $app['validator'] = $validator = m::mock('\Illuminate\Validation\Validator')->makePartial();
        $app['orchestra.role'] = $role = m::mock('Role');
        $app['orchestra.user'] = $user = m::mock('User');
        $app['orchestra.messages'] = $messages = m::mock('\Orchestra\Support\Messages')->makePartial();
        $app['events'] = $events = m::mock('\Illuminate\Events\Dispatcher')->makePartial();
        $app['orchestra.memory'] = $memory = m::mock('\Orchestra\Memory\MemoryManager')->makePartial();
        $app['config'] = $config = m::mock('\Illuminate\Config\Repository')->makePartial();
        $app['orchestra.acl'] = $acl = m::mock('Acl');

        $memoryProvider = m::mock('\Orchestra\Memory\Provider')->makePartial();
        $aclFluent = m::mock('\Orchestra\Auth\Acl\Fluent');
        $aclFluent->shouldReceive('attach')->twice()->andReturnNull();

        $input = $this->getUserInput();
        $rules = $this->getValidationRules();

        $validator->shouldReceive('make')->once()->with($input, $rules)->andReturn($validator)
            ->shouldReceive('fails')->once()->andReturn(false);
        $user->shouldReceive('newQuery')->once()->andReturn($user)
            ->shouldReceive('all')->once()->andReturnNull()
            ->shouldReceive('newInstance')->once()->andReturn($user)
            ->shouldReceive('fill')->once()->andReturnNull()
            ->shouldReceive('save')->once()->andReturnNull()
            ->shouldReceive('roles')->once()->andReturn($user)
            ->shouldReceive('sync')->once()->with(array(1))->andReturnNull();
        $role->shouldReceive('newQuery')->once()->andReturn($role)
            ->shouldReceive('lists')->once()->with('name', 'id')->andReturn(array('admin', 'member'));
        $events->shouldReceive('fire')->once()->with('orchestra.install: user', array($user, $input))->andReturnNull()
            ->shouldReceive('fire')->once()->with('orchestra.install: acl', array($acl))->andReturnNull();
        $memory->shouldReceive('make')->once()->andReturn($memoryProvider);
        $memoryProvider->shouldReceive('put')->once()->with('site.name', $input['site_name'])->andReturnNull()
            ->shouldReceive('put')->once()->with('site.theme', array('frontend' => 'default', 'backend' => 'default'))
                ->andReturnNull()
            ->shouldReceive('put')->once()->with('email', 'email-config')->andReturnNull()
            ->shouldReceive('put')->once()->with('email.from', array('name' => $input['site_name'], 'address' => $input['email']))
                ->andReturnNull();
        $config->shouldReceive('get')->once()->with('orchestra/foundation::roles.admin', 1)->andReturn(1)
            ->shouldReceive('get')->once()->with('mail')->andReturn('email-config');
        $acl->shouldReceive('make')->once()->with('orchestra')->andReturn($acl)
            ->shouldReceive('actions')->once()->andReturn($aclFluent)
            ->shouldReceive('roles')->once()->andReturn($aclFluent)
            ->shouldReceive('allow')->once()->andReturnNull()
            ->shouldReceive('attach')->once()->with($memoryProvider)->andReturnNull();

        $messages->shouldReceive('add')->once()->with('success', m::any())->andReturnNull();

        $stub = new Installation($app);
        $this->assertTrue($stub->createAdmin($input, false));
    }

    /**
     * Test Orchestra\Foundation\Installation\Installation::createAdmin() method
     * with validation errors.
     *
     * @test
     */
    public function testCreateAdminMethodWithValidationErrors()
    {
        $app = $this->app;
        $app['files'] = m::mock('\Illuminate\Filesystem\Filesystem');
        $app['validator'] = $validator = m::mock('\Illuminate\Validation\Validator')->makePartial();
        $app['session'] = $session = m::mock('Session\Store');

        $input = $this->getUserInput();
        $rules = $this->getValidationRules();

        $validator->shouldReceive('make')->once()->with($input, $rules)->andReturn($validator)
            ->shouldReceive('passes')->once()->andReturn(false)
            ->shouldReceive('messages')->once()->andReturn('foo-errors');
        $session->shouldReceive('flash')->once()->with('errors', 'foo-errors')->andReturnNull();

        $stub = new Installation($app);
        $this->assertFalse($stub->createAdmin($input));
    }

    /**
     * Test Orchestra\Foundation\Installation\Installation::createAdmin() method
     * throws exception.
     *
     * @test
     */
    public function testCreateAdminMethodThrowsException()
    {
        $app = $this->app;
        $app['files'] = m::mock('\Illuminate\Filesystem\Filesystem');
        $app['validator'] = $validator = m::mock('\Illuminate\Validation\Validator')->makePartial();
        $app['orchestra.user'] = $user = m::mock('User');
        $app['orchestra.messages'] = $messages = m::mock('\Orchestra\Support\Messages')->makePartial();

        $input = $this->getUserInput();
        $rules = $this->getValidationRules();

        $validator->shouldReceive('make')->once()->with($input, $rules)->andReturn($validator)
            ->shouldReceive('fails')->once()->andReturn(false);
        $user->shouldReceive('newQuery')->once()->andReturn($user)
            ->shouldReceive('all')->once()->andReturn(array('not so empty'));
        $messages->shouldReceive('add')->once()->with('error', m::any())->andReturnNull();

        $stub = new Installation($app);
        $this->assertFalse($stub->createAdmin($input, false));
    }
}
