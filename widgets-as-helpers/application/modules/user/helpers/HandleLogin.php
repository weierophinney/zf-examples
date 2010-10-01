<?php

class User_Helper_HandleLogin extends Zend_Controller_Action_Helper_Abstract
{
    public function preDispatch()
    {
        if (null === ($controller = $this->getActionController())) {
            return;
        }

        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()) {
            $this->handleLogin();
            return;
        }

        $this->createProfileWidget();
    }

    public function createProfileWidget()
    {
        if (!$view = $this->getView()) {
            return;
        }

        $view->profile = $view->partial('profile.phtml', array(
            'identity' => Zend_Auth::getInstance()->getIdentity(),
        ));
    }

    public function renderLoginForm(Zend_Form $form, $error = null)
    {
        if (!$view = $this->getView()) {
            return;
        }

        $view->profile = $view->partial('login.phtml', array(
            'form'  => $form,
            'error' => $error,
        ));
    }

    public function handleLogin()
    {
        $request = $this->getRequest();
        $form    = new User_Form_Login();

        if (!$request->isPost()) {
            $this->renderLoginForm($form);
        }

        if (!$form->isValid($request->getPost())) {
            $this->renderLoginForm($form);
            return;
        }

        $config   = $this->getConfig();
        $username = $form->username->getValue();
        $password = $form->password->getValue();
        $password = substr($username, 0, 3) . $password . $config->salt;
        $password = hash('sha256', $password);

        $adapter = new Zend_Auth_Adapter_DbTable(
            Zend_Db_Table_Abstract::getDefaultAdapter(),
            $config->adapter->table,
            $config->adapter->identity_column,
            $config->adapter->password_column
        );
        $adapter->setIdentity($username)
                ->setCredential($password);

        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        if (!$result->isValid()) {
            $this->renderLoginForm($form, 'Invalid Credentials');
            return;
        }

        $auth->getStorage()->write(
            $adapter->getResultRowObject(null, 'password')
        );

        $this->createProfileWidget();
    }

    public function getConfig()
    {
        if (null === $this->config) {
            $this->config = new Zend_Config_Ini(dirname(__FILE__) . '/../configs/user.ini', APPLICATION_ENV);
        }
        return $this->config;
    }

    public function getView()
    {
        $controller = $this->getActionController();
        $view = $controller->view;
        if (!$view instanceof Zend_View_Abstract) {
            return;
        }
        $view->addScriptPath(dirname(__FILE__) . '/../views/scripts')
             ->addHelperPath(dirname(__FILE__) . '/../views/helpers', 'User_View_Helper');
        return $view;
    }
}
