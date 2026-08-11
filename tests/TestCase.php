<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        if ($this->app->bound('session.store')) {
            $this->app['session.store']->flush();
        }
    }
}
