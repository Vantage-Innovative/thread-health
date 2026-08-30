<?php

namespace Vantage\ThreadHealth\Facades;

use Illuminate\Support\Facades\Facade;

/** @method static int report() */
class ThreadHealth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Vantage\ThreadHealth\ThreadHealthReporter::class;
    }
}
