<?php

class User_Bootstrap extends Zend_Application_Module_Bootstrap
{
    public function initResourceLoader()
    {
        $loader = $this->getResourceLoader();
        $loader->addResourceType('helper', 'helpers', 'Helper');
    }

    protected function _initConfig()
    {
        $env = $this->getEnvironment();
        $config = new Zend_Config_Ini(
            dirname(__FILE__) . '/configs/user.ini', 
            $this->getEnvironment()
        );
        return $config;
    }

    protected function _initHelpers()
    {
        $this->bootstrap('config');
        $config = $this->getResource('config');

        Zend_Controller_Action_HelperBroker::addHelper(
            new User_Helper_HandleLogin($config)
        );
    }
}
