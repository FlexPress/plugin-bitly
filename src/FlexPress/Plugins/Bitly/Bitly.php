<?php

namespace FlexPress\Plugins\Bitly;

use FlexPress\Components\Hooks\Hooker;
use FlexPress\Plugins\AbstractPlugin;

class Bitly extends AbstractPlugin
{
    /**
     * @var \FlexPress\Components\Hooks\Hooker
     */
    protected $hooker;

    public function __construct(Hooker $hooker)
    {
        $this->hooker = $hooker;
    }

    /**
     *
     * Used to setup the hooker
     *
     * @param $file
     * @author Tim Perry
     *
     */
    public function init($file)
    {
        parent::init($file);
        $this->hooker->hookUp();
    }
}
