<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $briEnvFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->briEnvFile = tempnam(sys_get_temp_dir(), 'emadrasah-bri-env-');
        file_put_contents($this->briEnvFile, "APP_NAME=E-Madrasah\nUNRELATED=preserved\n");
        config(['bri.env_file' => $this->briEnvFile]);
    }

    protected function tearDown(): void
    {
        @unlink($this->briEnvFile);
        parent::tearDown();
    }

    //
}
