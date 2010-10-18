<?php

/**
 * Switch layout if mobile device detected
 * 
 */
class Ma_Plugin_Layout extends Zend_Controller_Plugin_Abstract
{
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $front     = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $ua        = $bootstrap->getResource('useragent');
        $device    = $ua->getDevice();

        if ($device->getType() == 'mobile') {
            Zend_Layout::getMvcInstance()->setLayout('mobile');
        }
    }
}
