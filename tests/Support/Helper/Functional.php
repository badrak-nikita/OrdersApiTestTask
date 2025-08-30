<?php

namespace Tests\Support\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module\Doctrine;

class Functional extends \Codeception\Module
{
    /**
     * @throws ModuleException
     */
    public function _initialize(): void
    {
        $this->getModule('Doctrine');
    }
}
