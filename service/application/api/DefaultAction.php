<?php namespace service\application\api;


class DefaultAction extends AbstractAction
{

    # Test
    public function test($p, $debug)
    {
        unset($p, $debug);

        return 'hello, world!';
    }

}

