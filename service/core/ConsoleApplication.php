<?php namespace service\core;

use yii\console\Application as YiiConsoleApplication;
use service\core\db\DatabaseQuery;

class ConsoleApplication extends YiiConsoleApplication
{

    ############################################################
    # 修改原来的应用程序逻辑

    public $defaultRoute = 'default';

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        # 加载必需文件
        $this->_load_necessary_file();

        # 加载数据表别名
        $this->_load_table_alias();
    }


    ############################################################
    # 自定义应用程序逻辑

    # 加载必需文件
    private function _load_necessary_file()
    {
        # 取得要加载的必需文件列表
        $file_list = $this->params['necessaryFiles'];
        if (!is_array($file_list) || !$file_list) return;

        # 依次加载文件
        $include_path = $this->params['includePath'];
        foreach($file_list as $filename) {
            $filepath = $include_path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($filepath)) {
                /** @noinspection PhpIncludeInspection */
                include $filepath;
            }
        }
    }

    # 加载数据表别名
    private function _load_table_alias()
    {
        # 取得数据表别名配置
        $alias_list = $this->params['tableAliases'];
        if (!is_array($alias_list) || !$alias_list) return;

        # 依次加载数据表别名文件
        $include_path = $this->params['includePath'];
        foreach($alias_list as $handle_key => $filename) {
            $filepath = implode(DIRECTORY_SEPARATOR, [$include_path, 'tablealias', $filename]);
            if (file_exists($filepath)) {
                /** @noinspection PhpIncludeInspection */
                DatabaseQuery::set_global_alias_map(include $filepath, $handle_key);
            }
        }
    }

}

