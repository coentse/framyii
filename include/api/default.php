<?php
use service\core\controller\ActionConfig as Action;
use service\core\controller\Parameter;

############################################################
# 默认模块：default

# 操作：默认操作
Action::make('default', 'default')
    ->callMethod('DefaultAction@test')
    ->outputMode('string')
    ->debug(0)
    ->attach();

