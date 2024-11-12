<?php

namespace Orchestra\Installation\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Mockery as m;
use Orchestra\Installation\Installation;
use Illuminate\Support\Facades\File;

class InstallationTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
    {
        $this->artisan('migrate:reset');

        parent::tearDown();
    }

    /** @test */
    public function it_can_boot_installer_files()
    {
        // Set up the config with the new format
        $this->app['config']->set('orchestra.installer.installers.paths', [
            '/path/to/installer'
        ]);

        // Mock the File facade
        File::shouldReceive('exists')
            ->once()
            ->with('/path/to/installer/installer.php')
            ->andReturn(false);

        File::shouldReceive('getRequire')
            ->never();

        $stub = new Installation();
        $result = $stub->bootInstallerFiles();

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_migrate_installer()
    {
        $this->assertNotInstalled();

        $stub = new Installation();
        $this->assertTrue($stub->migrate());

        $this->assertInstalled();
    }

    /** @test */
    public function it_can_run_installation()
    {
        $data = [
            'site_name' => 'Orchestra Platform',
            'email' => 'admin@orchestraplatform.com',
            'password' => 'secret',
            'fullname' => 'Administrator',
        ];

        $this->assertNotInstalled();

        $stub = new Installation();

        $this->assertTrue($stub->migrate());

        $stub->make($data, false);

        $this->assertInstalled();

        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
            'fullname' => $data['fullname'],
        ]);

        $this->assertDatabaseHas('user_role', [
            'user_id' => 1,
            'role_id' => 1,
        ]);
    }

    /** @test */
    public function it_cant_run_installation_due_to_validation_fails()
    {
        $this->expectException('Illuminate\Validation\ValidationException');
        $this->expectExceptionMessage('The email must be a valid email address.');

        $data = [
            'site_name' => 'Orchestra Platform',
            'email' => 'admin[at]orchestraplatform.com',
            'password' => 'secret',
            'fullname' => 'Administrator',
        ];

        $this->assertNotInstalled();

        $stub = new Installation();

        $this->assertTrue($stub->migrate());

        $stub->make($data, false);
    }

    /** @test */
    public function it_cant_run_installation_due_existing_users()
    {
        $this->expectException('Exception');
        $this->expectExceptionMessage('Unable to install when there already user registered.');

        $adminUser = $this->runInstallation();

        $data = [
            'site_name' => 'Orchestra Platform',
            'email' => 'admin@orchestraplatform.com',
            'password' => 'secret',
            'fullname' => 'Administrator',
        ];

        $stub = new Installation();
        $stub->make($data, false);
    }

    /**
     * Assert not yet installed.
     *
     * @return void
     */
    protected function assertNotInstalled(): void
    {
        $this->assertFalse(Schema::hasTable('password_resets'));
        $this->assertFalse(Schema::hasTable('orchestra_options'));
        $this->assertFalse(Schema::hasTable('roles'));
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('user_meta'));
        $this->assertFalse(Schema::hasTable('user_role'));
    }

    /**
     * Assert has been installed.
     *
     * @return void
     */
    protected function assertInstalled(): void
    {
        $this->assertTrue(Schema::hasTable('password_resets'));
        $this->assertTrue(Schema::hasTable('orchestra_options'));
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('user_role'));
    }
}
