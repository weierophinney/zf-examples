<?php

class MobileController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }

    public function detailsAction()
    {
        $ua = $this->getInvokeArg('bootstrap')->getResource('useragent');
                        $this->view->device = $ua->getDevice();
    }

    public function imageAction()
    {
        // action body
    }

    public function myDeviceAction()
    {
        // action body
    }


}




