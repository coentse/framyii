<?php

/*
 * 检测当前控制器的模块名
 *
 * @param string|array $modules
 * @return bool
 */
function check_module($modules)
{
    # 从当前路由分解出当前的模块(即Yii中的控制器ID)
    $_array = explode('/', Yii::$app->controller->getRoute());
    array_pop($_array);
    $current_module = implode('/', $_array);
    unset($_route, $_array);

    # 检测当前模块是否与指定的模块相匹配
    if (is_array($modules)) {
        return in_array($current_module, $modules);
    }
    else {
        $modules = trim($modules, '/');
        return $current_module == $modules;
    }
}

/*
 * 检测当前控制器的操作名
 *
 * @param string $action
 * @return bool
 */
function check_action($actions)
{
    # 取得当前的操作(即Yii中的路由)
    $current_action = Yii::$app->controller->getRoute();

    # 检测当前操作是否与指定的操作相匹配
    if (is_array($actions)) {
        return in_array($current_action, $actions);
    }
    else {
        $actions = trim($actions, '/');
        return $current_action == $actions;
    }
}

/*
 * 检测当前控制器的前缀路径
 *
 * @param string|array $paths
 * @return bool
 */
function check_path($paths)
{
    # 对指定的路径进行处理
    if (!is_array($paths)) $paths = [$paths];

    # 取得当前的操作(即Yii中的路由)
    $current_route = Yii::$app->controller->getRoute();

    # 依次检测各个路径是否和当前路由匹配
    foreach($paths as $path) {
        $_len = strlen($path);
        if ($_len <= strlen($current_route)) {
            if ($path == substr($current_route, 0, $_len)) {
                return true;
            }
        }
        unset($_len, $path);
    }

    return false;
}


############################################################
# URL处理操作

/*
 * 使用给定的参数生成URL
 * @param string|array  $route      操作路由
 * @param integer $append_return    是否为URL追加返回地址；“1”为追加当前地址；
 *                                  “2”为追加当前页面的返回地址，如不存在则追加当前页面地址
 */
function make_url($route, $param=array(), $append_return=0)
{
    # 处理指定的操作路由
    $url = yii\helpers\Url::toRoute($route);

    # 处理指定的参数
    if ($param && is_array($param)) {
        $_params = [];
        foreach($param as $_key => $_val) {
            if (is_null($_val)) continue;
            $_params[] = $_key ."=". urlencode($_val);
        }
        $_join = (strpos($url, '?') === false)? '?': '&';
        $url  .= $_join . implode('&', $_params);
        unset($_join, $_params, $_key, $_val);
    }

    # 处理返回地址
    if ($append_return) {
        if ($append_return == 2) {
            $_return = get_return_url(null, true);
        }
        else {
            $_return = get_current_url(true, true);
        }
        if ($_return) {
            $_join = (strpos($url, '?') === false)? '?': '&';
            $url  .= $_join . 'return='. $_return;
        }
        unset($_join, $_return);
    }

    if (!$url) $url = '?';
    return $url;
}

# 取得返回地址
function get_return_url($default=null, $encode=false)
{
    $url = service\core\SystemVariable::httpGet('return');
    if (!$url) $url = $default;
    if (!$url) return null;
    return $encode? urlencode($url): $url;
}

# 取得当前地址
function get_current_url($encode=false, $only_qs=false)
{
    if (!$only_qs || Yii::$app->components['urlManager']['enablePrettyUrl']) {
        $url = yii\helpers\Url::current();
    } else {
        $url = service\core\SystemVariable::server('QUERY_STRING');
        if ($url) $url = '?' . $url;
    }
    if ($encode) return urlencode($url);
    return $url;
}

# 取得当前完整地址
function get_current_full_url($encode=false)
{
    $url = yii\helpers\Url::current([], true);
    if ($encode) return urlencode($url);
    return $url;
}


############################################################
# 调试输出操作

# 抛出调试数据
function dump($data, $is_exit=true)
{
    if (!headers_sent()) {
        header("Content-type: text/html; charset=utf-8");
    }
    echo "<pre>\n";
    print_r($data);
    echo "\n</pre>\n";
    if ($is_exit) exit();
    return null;
}

# 抛出JSON数据
function dump_json($data)
{
    if (!headers_sent()) {
        header("Content-type: text/html; charset=utf-8");
    }
    echo json_encode($data);
    exit();
}

# 抛出提示信息
function dump_msg($message, $code=null, $title='Message')
{
    if (!headers_sent()) {
        header("Content-type: text/html; charset=utf-8");
    }
    echo <<<EOT
<h1>{$title}</h1>
<strong><code>{$code}</code></strong>
<p>{$message}</p>
EOT;
    exit;
}

# 抛出异常
function dump_exception(Exception $e)
{
    # 组合异常信息
    $message = "Exception: ". get_class($e) ."\n\n";
    $_code = $e->getCode();
    if ($_code) {
        $message .= "error: ". $_code .": ". $e->getMessage() ."\n";
    }
    else {
        $message .= "error: ". $e->getMessage() ."\n";
    }
    $message .= " line: ". $e->getLine() ."\n";
    $message .= " file: ". $e->getFile() ."\n\n";
    $message .= "++++++++++ traces ++++++++++\n";
    $_traces = $e->getTrace();
    foreach($_traces as $_t) {
        $_file = $_t['file'];
        $message .= '> '. $_t['line'] .' # '. $_file;
        if ($_t['class']) {
            $message .= ', '. $_t['class'] .'->'. $_t['function'] ."\n";
        }
        else {
            $message .= ', '. $_t['function'] ."\n";
        }
    }
    $message .= "++++++++++++++++++++++++++++\n";

    # 隐藏真实文件路径
    $rootpath = realpath(__DIR__ .'/../') .'/';
    $message = str_replace($rootpath, '', $message);
    dump($message);
}

# 抛出SQL语句
function dump_sql($sql, $param=null)
{
    dump($sql, false);
    dump($param);
}


############################################################
# 数组相关操作

# 功能同 PHP 5.5 的 array_column
if (!function_exists('array_column')) {
    function array_column(array $input, $column_key, $index_key = null)
    {
        $data = array();

        # 没有指定 $index_key 时的处理
        if (is_null($index_key)) {
            if (is_null($column_key)) return $input;
            foreach($input as $entry) {
                if (in_array($entry[$column_key], $data)) continue;
                $data[] = $entry[$column_key];
            }
            return $data;
        }

        # 指定了 $index_key 时的处理
        foreach($input as $entry) {
            if (is_null($column_key)) {
                $data[$entry[$index_key]] = $entry;
            }
            else {
                $data[$entry[$index_key]] = $entry[$column_key];
            }
        }
        return $data;
    }
}

# 取得二维数组中指定键名对应的值列表
function get_array_unique_value(array $input, $column_key, $return='array')
{
    $data = array();
    foreach($input as $entry) {
        if (isset($entry[$column_key]) && !in_array($entry[$column_key], $data)) {
            array_push($data, $entry[$column_key]);
        }
    }
    if ($return == 'array') return $data;
    return implode(',', $data);
}

# 将二维数组的键名修改为指定的键值
function change_array_key_by_value(array $input, $index_key)
{
    $data = array();
    foreach($input as $entry) {
        $data[$entry[$index_key]] = $entry;
    }
    return $data;
}

# 将二维数组按指定的键名进行分组
function group_array_by_key(array $input, $index_key)
{
    $data = array();
    foreach($input as $entry) {
        if (!$data[$entry[$index_key]]) {
            $data[$entry[$index_key]] = array();
        }
        $data[$entry[$index_key]][] = $entry;
    }
    return $data;
}

/*
 * 对比新旧两个二维数组后，取得被修改项的原值和新值
 * 注：只比较"$new_array"中存在的项
 */
function compare_2d_array($old_array, $new_array)
{
    $change_data_old = [];
    $change_data_new = [];
    foreach($new_array as $key => $val) {
        if ($old_array[$key] == $val) {
            continue;
        } else {
            $change_data_old[$key] = $old_array[$key];
            $change_data_new[$key] = $val;
        }
    }
    return [$change_data_old, $change_data_new];
}


############################################################
# HTTP/HTTPS 提交数据操作

# 通过 HTTP 协议以 GET/POST 方式提交内容
function http_fetch($url, $post_data=NULL, $timeout=5)
{
    # 创建 curl 对象
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($post_data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    # 从指定的 URL 获取数据
    $result = curl_exec($ch);

    # 释放 curl 对象
    curl_close($ch);
    unset($ch);

    return $result;
}

/*
 *  通过 HTTPS 协议以 GET/POST 方式提交内容
 *  @param string  $url        服务器地址
 *  @param array   $post_data  POST数据
 *  @param array   $ssl_param  SSL证书相关参数
 *      array(
 *          'disable_peer_verify' => (bool),     # 是否从服务端进行验证
 *          'disable_host_verify' => (bool),     # 是否验证服务器SSL证书的公用名（common name）
 *          'certificate_file'    => (string),   # SSL证书文件
 *          'private_key_file'    => (string),   # SSL私钥文件（如果私钥文件已于证书文件合并，可省略此项）
 *      );
 *  @param integer $timeout    超时时间
 */
function https_fetch($url, $post_data=NULL, $ssl_param, $timeout=5)
{
    # 创建 curl 对象
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    # 是否禁用证书验证
    if ($ssl_param['disable_peer_verify']) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    if ($ssl_param['disable_host_verify']) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    # 指定私钥、证书
    if ($ssl_param['private_key_file']
        && file_exists($ssl_param['private_key_file'])
        && file_exists($ssl_param['certificate_file'])) {
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $ssl_param['certificate_file']);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY,  $ssl_param['private_key_file']);
    }
    elseif ($ssl_param['certificate_file']
        && file_exists($ssl_param['certificate_file'])) {
        curl_setopt($ch, CURLOPT_SSLCERT, $ssl_param['certificate_file']);
    }

    # 是否为 POST 提交方式
    if ($post_data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    # 从指定的 URL 获取数据
    $result = curl_exec($ch);

    # 释放 curl 对象
    curl_close($ch);
    unset($ch);

    return $result;
}

if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
        . ($postname ?: basename($filename))
        . ($mimetype ? ";type=$mimetype" : '');
    }
}

# HTTP上传文件操作
function http_upload($url, $file_list, $param=array(), $timeout=5)
{
    # 处理提交参数
    if (!is_array($param)) throw new \Exception('param format error');
    foreach($file_list as $key => $filepath) {
        $filetype = mime_content_type($filepath);
        $filename = basename($filepath);
        $param[$key] = curl_file_create($filepath, $filetype, $filename);
    }
    //dump($param);

    # 创建 curl 对象
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if (version_compare(PHP_VERSION, '5.5', '<')) {
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
    }
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

    # 从指定的 URL 获取数据
    $result = curl_exec($ch);

    # 释放 curl 对象
    curl_close($ch);
    unset($ch);

    return $result;
}


############################################################
# 其它常用函数

# 生成Yii的重定向响应
function yii_redirect_response($url, $statusCode=302)
{
    $response = Yii::$app->getResponse();
    $response->redirect($url, $statusCode);
    return $response;
}

# 转向函数
function redirect($url, $msg=null)
{
    # 无提示信息转向
    if (!$msg) {
        header("Location:". $url);
        echo '<meta http-equiv="refresh" content="0; URL='. $url .'">';
        exit();
    }

    # 有提示信息转向
    $msg = str_replace("\n", '\n', $msg);
    $msg = str_replace('"' ,  "'", $msg);
    echo "<html>\n";
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n";
    echo "<body>\n";
    echo '<script>'."\n".'if (alert("'.$msg.'")) {'."\n".'window.location.href="'.$url.'";'."\n".'} else {'."\n".'window.location.href="'.$url.'";'."\n".'}'."\n".'</script>'."\n";
    echo "\n</body>\n</html>";
    exit();
}

# 取得远程客户端的IP地址
function get_remote_ip($type='ip')
{
    $ip = $_SERVER['HTTP_X_REMOTE_ADDR']? $_SERVER['HTTP_X_REMOTE_ADDR']: $_SERVER['REMOTE_ADDR'];
    if ($type == 'long') $ip = ip2long($ip);
    return $ip;
}

# 生成随机字符串
function create_random_string($len=15, $complex=false)
{
    # 生成加命种子
    $seed = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,0,1,2,3,4,5,6,7,8,9,_,-,.";
    if ($complex) $seed .= ',~,!,@,#,$,%,^,&,*,(,),+,=,{,},|,:,<,>,?,[,]';
    $array = explode(',', $seed);
    $count = count($array);

    # 生成随机字符串
    $string = '';
    for ($i=0; $i<$len; $i++) {
        $string .= $array[mt_rand(0, $count)];
    }
    return $string;
}

# 生成随机数字
function create_random_number($len=6)
{
    $number = "";
    for($i=0; $i<ceil($len / 4); $i++) {
        $number .= mt_rand(1000, 9999);
    }
    return substr($number, 0, $len);
}

# 生成UUID
function generate_uuid_v4()
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,
        // 48 bits for "node"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

# 按日期+时间规则生成定单号
function create_order_number($prefix='')
{
    $order_no = substr(date("YmdHis"), 2) .'-'. substr(explode(" ", microtime())[0], 2, 5);
    return $prefix . $order_no;
}

# 生成验证令牌
function create_token($key, $oncestr=null)
{
    if (!$oncestr) $oncestr = create_random_string(10);
    $token = md5($key .'|'. $oncestr);
    return [
        'token' => $token, 'oncestr' => $oncestr
    ];
}

# 密码Hash处理 & 密码验证
function hash_password($password, $salt=NULL)
{
    if (!$salt) {
        $salt = '$5$rounds=5000$'. create_random_string(15) .'$';
    }
    return crypt($password, $salt);
}
function verify_password($password, $hash)
{
    $salt = substr($hash, 0, 31);
    return hash_password($password, $salt) == $hash;
}

# 取得一周日期列表
function get_week_date_list($date='')
{
    $time = $date? strtotime($date): time();

    # 当前日期为一周的第几天（周日为7）
    $week_number = date("w", $time);
    if (!$week_number) $week_number = 7;

    # 取得周一的时间戳
    if ($week_number != 1) {
        $time = strtotime("-". ($week_number - 1) ." day", $time);
    }

    # 生成一周的数据
    $week_date_list = [];
    for($i=0; $i<7; $i++) {
        $week_date_list[] = [
            'date' => date("Y-m-d", $time),
            'wday' => get_week_chinese_name($i + 1),
        ];
        $time = strtotime("+1 day", $time);
    }

    return $week_date_list;
}

# 取得中文的周几名称
function get_week_chinese_name($number)
{
    $name_map = [
        0 => '日', 1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日'
    ];
    return $name_map[$number];
}

# 根据指定的文件名取得文件后缀
function parse_file_suffix($filename)
{
    $array = explode('.', $filename);
    return strtolower($array[count($array)]);
}

# 目录创建操作
function create_dir($dir_path)
{
    # 检测路径是否已存在
    if (file_exists($dir_path)) {
        if (is_dir($dir_path)) return true;
        return false;
    }
    # 创建目录
    return mkdir($dir_path, 0755, true);
}
function create_dir_by_file_path($file_path)
{
    return create_dir(dirname($file_path));
}


/**
 * 二维数组根据字段进行排序
 * @param array $array 需要排序的数组
 * @param string $field 排序的字段
 * @param string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
 * @return mixed
 */
function arraySort($array, $field, $sort = 'SORT_DESC')
{
    $arrSort = array();
    foreach ($array as $uniqid => $row) {
        foreach ($row as $key => $value) {
            $arrSort[$key][$uniqid] = $value;
        }
    }
    array_multisort($arrSort[$field], constant($sort), $array);
    return $array;
}

/**
 * 二维数组去重
 * @param $arr 传入数组
 * @param $key 判断的key值
 * @return array
 */
function arrayRemoveDuplicate($arr, $key){
    //建立一个目标数组
    $res = array();
    foreach ($arr as $value) {
        //查看有没有重复项
        if(isset($res[$value[$key]])){
            //有：销毁
            unset($value[$key]);
        }
        else{
            $res[$value[$key]] = $value;
        }
    }
    return $res;
}


/**
 * @name php获取中文字符拼音首字母
 */
function getCharters($zh){
    $ret = "";
    $s1 = iconv("UTF-8","gb2312", $zh);
    $s2 = iconv("gb2312","UTF-8", $s1);
    if($s2 == $zh){$zh = $s1;}
    for($i = 0; $i < strlen($zh); $i++){
        $s1 = substr($zh,$i,1);
        $p = ord($s1);
        if($p > 160){
            $s2 = substr($zh,$i++,2);
            $ret .= getfirstchar($s2);
        }else{
            $ret .= $s1;
        }
    }
    return strtolower($ret);
}

function getfirstchar($s0){
    $fchar = ord($s0{0});
    if($fchar >= ord( "A") and $fchar <= ord( "z") ) return strtoupper($s0{0});
    $s1 = iconv("UTF-8","gb2312", $s0);
    $s2 = iconv("gb2312","UTF-8", $s1);
    if($s2 == $s0){
        $s = $s1;
    }else{
        $s = $s0;
    }
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc >= -20319 and $asc <= -20284) return "A";
    if($asc >= -20283 and $asc <= -19776) return "B";
    if($asc >= -19775 and $asc <= -19219) return "C";
    if($asc >= -19218 and $asc <= -18711) return "D";
    if($asc >= -18710 and $asc <= -18527) return "E";
    if($asc >= -18526 and $asc <= -18240) return "F";
    if($asc >= -18239 and $asc <= -17923) return "G";
    if($asc >= -17922 and $asc <= -17418) return "I";
    if($asc >= -17417 and $asc <= -16475) return "J";
    if($asc >= -16474 and $asc <= -16213) return "K";
    if($asc >= -16212 and $asc <= -15641) return "L";
    if($asc >= -15640 and $asc <= -15166) return "M";
    if($asc >= -15165 and $asc <= -14923) return "N";
    if($asc >= -14922 and $asc <= -14915) return "O";
    if($asc >= -14914 and $asc <= -14631) return "P";
    if($asc >= -14630 and $asc <= -14150) return "Q";
    if($asc >= -14149 and $asc <= -14091) return "R";
    if($asc >= -14090 and $asc <= -13319) return "S";
    if($asc >= -13318 and $asc <= -12839) return "T";
    if($asc >= -12838 and $asc <= -12557) return "W";
    if($asc >= -12556 and $asc <= -11848) return "X";
    if($asc >= -11847 and $asc <= -11056) return "Y";
    if($asc >= -11055 and $asc <= -10247) return "Z";
    return '~' ;
}