<?php

class User_Bootstrap extends Zend_Application_Module_Bootstrap
{
    public function initResourceLoader()
    {
        $loader = $this->getResourceLoader();
        $loader->addResourceType('helper', 'helpers', 'Helper');
    }

    protected function _initHelpers()
    {
        Zend_Controller_Action_HelperBroker::addHelper(
            new User_Helper_HandleLogin()
        );
    }
}
