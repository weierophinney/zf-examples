<?php

class User_Form_Login extends Zend_Form
{
    public function init()
    {
        $this->setElementsBelongTo('login');
        $this->addElement('text', 'username', array(
            'label'    => 'Username: ',
            'required' => true,
        ));
        $this->addElement('password', 'password', array(
            'label'    => 'Password: ',
            'required' => true,
        ));
        $this->addElement('submit', 'login', array(
            'label'    => 'Login',
            'ignore'   => 'true',
            'required' => 'false',
        ));
    }
}
