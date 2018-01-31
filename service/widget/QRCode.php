<?php namespace service\widget;

use \Endroid\QrCode\QrCode as EndroidQrCode;

class QRCode
{

    # 显示二维码图像
    static public function show_qrcode($text='hello,world!', $size=300, $label='')
    {
        # 输出 HTTP 头
        header('Content-type: image/png');

        # 创建二维码并显示
        $qrCode = new EndroidQrCode();
        $qrCode->setText($text)
            ->setSize($size)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel($label)
            ->setLabelFontSize(16)
            ->render();
    }

    # 取得二维码图像数据
    static public function get_qrcode($text='hello,world!', $size=300, $label='')
    {
        $qrCode = new EndroidQrCode();
        $data = $qrCode->setText($text)
            ->setSize($size)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
            ->setLabel($label)
            ->setLabelFontSize(16)
            ->getImage();
        return $data;
    }

}


