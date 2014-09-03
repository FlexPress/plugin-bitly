<?php

namespace FlexPress\Plugins\Bitly\DependencyInjection;

use FlexPress\Components\Hooks\Hooker;
use FlexPress\Plugins\Bitly\Bitly as BitlyPlugin;
use FlexPress\Plugins\Bitly\Hooks\Bitly as BitlyHookable;

class DependencyInjectionContainer extends \Pimple
{

    public function init()
    {

        $this['objectStorage'] = function () {
            return new \SplObjectStorage();
        };

        $this['bitlyHookable'] = function () {
            return new BitlyHookable();
        };

        $this['hooker'] = function ($c) {
            return new Hooker($c['objectStorage'], array(
                $c['bitlyHookable']
            ));
        };

        $this['bitlyPlugin'] = function ($c) {
            return new BitlyPlugin($c['hooker']);
        };

    }
}
