<?php namespace Orchestra\Installation\Routing\TestCase;

use Mockery as m;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Orchestra\Foundation\Testing\TestCase;

class InstallerControllerTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        parent::tearDown();

        m::close();
    }

    /**
     * Get package providers.
     *
     * @return array
     */
    protected function getPackageProviders()
    {
        return [
            'Orchestra\Installation\InstallerServiceProvider',
        ];
    }

    /**
     * Test GET /admin/install
     *
     * @test
     */
    public function testGetIndexAction()
    {
        $dbConfig = array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'database',
            'username'  => 'root',
            'password'  => 'root',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        );

        $requirement = m::mock('\Orchestra\Contracts\Installation\Requirement');
        $requirement->shouldReceive('check')->once()->andReturn(true)
            ->shouldReceive('getChecklist')->once()->andReturn(array(
                'databaseConnection' => array(
                    'is'       => true,
                    'should'   => true,
                    'explicit' => true,
                    'data'     => array(),
                ),
            ));
        $user = m::mock('UserEloquent', '\Orchestra\Model\User');
        App::bind('UserEloquent', function () use ($user) {
            return $user;
        });
        App::bind('Orchestra\Contracts\Installation\Requirement', function () use ($requirement) {
            return $requirement;
        });
        Config::set('database.default', 'mysql');
        Config::set('auth', array('driver' => 'eloquent', 'model' => 'UserEloquent'));
        Config::set('database.connections.mysql', $dbConfig);

        $this->call('GET', 'admin/install');
        $this->assertResponseOk();
        $this->assertViewHasAll(array(
            'database',
            'auth',
            'authentication',
            'installable',
            'checklist'
        ));
    }

    /**
     * Test GET /admin/install when auth driver is not Eloquent.
     *
     * @test
     */
    public function testGetIndexActionWhenAuthDriverIsNotEloquent()
    {
        $dbConfig = array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'database',
            'username'  => 'root',
            'password'  => 'root',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        );

        $installer = m::mock('\Orchestra\Contracts\Installation\Installation');
        $installer->shouldReceive('bootInstallerFiles')->once()->andReturnNull();

        App::bind('Orchestra\Contracts\Installation\Installation', function () use ($installer) {
            return $installer;
        });

        $requirement = m::mock('\Orchestra\Contracts\Installation\Requirement');
        $requirement->shouldReceive('check')->once()->andReturn(true)
            ->shouldReceive('getChecklist')->once()->andReturn(array(
                'databaseConnection' => array(
                    'is'       => true,
                    'should'   => true,
                    'explicit' => true,
                    'data'     => array(),
                ),
            ));

        App::bind('Orchestra\Contracts\Installation\Requirement', function () use ($requirement) {
            return $requirement;
        });

        Config::set('database.default', 'mysql');
        Config::set('auth', array('driver' => 'eloquent', 'model' => 'UserNotAvailableForAuthModel'));
        Config::set('database.connections.mysql', $dbConfig);

        $this->call('GET', 'admin/install');
        $this->assertResponseOk();
        $this->assertViewHasAll(array(
            'database',
            'auth',
            'authentication',
            'installable',
            'checklist'
        ));
    }

    /**
     * Test GET /admin/install/prepare
     *
     * @test
     */
    public function testGetPrepareAction()
    {
        $installer = m::mock('\Orchestra\Contracts\Installation\Installation');
        $installer->shouldReceive('bootInstallerFiles')->once()->andReturnNull()
            ->shouldReceive('migrate')->once()->andReturnNull();

        App::bind('Orchestra\Contracts\Installation\Installation', function () use ($installer) {
            return $installer;
        });

        $this->call('GET', 'admin/install/prepare');
        $this->assertRedirectedTo(handles('orchestra::install/create'));
    }

    /**
     * Test GET /admin/install/create
     *
     * @test
     */
    public function testGetCreateAction()
    {
        $this->call('GET', 'admin/install/create');
        $this->assertResponseOk();
        $this->assertViewHas('siteName', 'Orchestra Platform');
    }

    /**
     * Test GET /admin/install/create
     *
     * @test
     */
    public function testPostCreateAction()
    {
        $input = array();
        $installer = m::mock('\Orchestra\Contracts\Installation\Installation');
        $installer->shouldReceive('bootInstallerFiles')->once()->andReturnNull()
            ->shouldReceive('createAdmin')->once()->with($input)->andReturn(true);

        App::bind('Orchestra\Contracts\Installation\Installation', function () use ($installer) {
            return $installer;
        });

        $this->call('POST', 'admin/install/create', $input);
        $this->assertRedirectedTo(handles('orchestra::install/done'));
    }

    /**
     * Test GET /admin/install/create when create admin failed.
     *
     * @test
     */
    public function testPostCreateActionWhenCreateAdminFailed()
    {
        $input = array();
        $installer = m::mock('\Orchestra\Contracts\Installation\Installation');
        $installer->shouldReceive('bootInstallerFiles')->once()->andReturnNull()
            ->shouldReceive('createAdmin')->once()->with($input)->andReturn(false);

        App::bind('Orchestra\Contracts\Installation\Installation', function () use ($installer) {
            return $installer;
        });

        $this->call('POST', 'admin/install/create', $input);
        $this->assertRedirectedTo(handles('orchestra::install/create'));
    }

    /**
     * Test GET /admin/install/done
     *
     * @test
     */
    public function testGetDoneAction()
    {
        $this->call('GET', 'admin/install/done');
        $this->assertResponseOk();
    }
}
