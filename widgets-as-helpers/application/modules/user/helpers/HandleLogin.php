<?php

class User_Helper_HandleLogin extends Zend_Controller_Action_Helper_Abstract
{
    protected $config;
    protected $view;

    public function __construct(Zend_Config $config)
    {
        $this->config = $config;
    }

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

        $view->user = $view->partial('profile.phtml', array(
            'identity' => Zend_Auth::getInstance()->getIdentity(),
        ));
    }

    public function renderLoginForm(Zend_Form $form, $error = null)
    {
        if (!$view = $this->getView()) {
            return;
        }

        $view->user = $view->partial('login.phtml', array(
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
            return;
        }

        if (!$form->isValid($request->getPost())) {
            $this->renderLoginForm($form);
            return;
        }

        $username = $form->username->getValue();
        $password = $form->password->getValue();
        $password = substr($username, 0, 3) . $password . $this->config->salt;
        $password = hash('sha256', $password);

        $adapter = new Zend_Auth_Adapter_DbTable(
            Zend_Db_Table_Abstract::getDefaultAdapter(),
            $this->config->adapter->table,
            $this->config->adapter->identity_column,
            $this->config->adapter->password_column
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

    public function getView()
    {
        if (null !== $this->view) {
            return $this->view;
        }

        $controller = $this->getActionController();
        $view = $controller->view;
        if (!$view instanceof Zend_View_Abstract) {
            return;
        }
        $view->addScriptPath(dirname(__FILE__) . '/../views/scripts');
        $this->view = $view;
        return $view;
    }
}
