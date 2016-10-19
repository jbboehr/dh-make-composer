<?php

namespace DhMakeComposer;

use Symfony\Component\Console\Application as BasicApplication;

class Application extends BasicApplication
{
    public function __construct()
    {
        parent::__construct('dh-make-composer');
        $this->add(new LockCommand());
        $this->add(new CreateCommand());
    }
}
