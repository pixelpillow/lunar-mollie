<?php

namespace Pixelpillow\LunarMollie\Facades;

use Illuminate\Support\Facades\Facade;
use Pixelpillow\LunarMollie\Managers\MollieManager;

class MollieFacade extends Facade
{
    /**
     * {@inheritdoc}
     *
     * @return MollieManager;
     */
    protected static function getFacadeAccessor()
    {
        return 'gc:mollie';
    }
}
