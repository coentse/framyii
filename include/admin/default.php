<?php
use service\core\controller\ActionConfig as Action;
use service\core\controller\Parameter;

############################################################
# 默认模块：default

# 操作：默认操作
Action::make('default', 'default')
    ->callMethod('DefaultAction@test')
    ->outputMode('string')
    ->parameters([
        Parameter::make('id')->invoke('get', 'id'),
    ])
    ->debug(-2)
    ->attach();

# 操作：默认操作2
Action::make('default', 'test2')
    ->callMethod('DefaultAction@test2')
    ->outputMode('string')
    ->parameters([
    ])
    ->debug(-2)
    ->attach();

