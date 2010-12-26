<?php

  class ActionTemplate extends Template {

    protected $controller;
    protected $action;

    function __construct($controller, $action, $variables) {
      $this->controller = $controller;
      $this->action = $action;
      $filename = ROOT_PATH . DS . 'app' . DS . 'views' . DS . $this->controller . DS . $this->action . '.php';
      parent::__construct($filename, $variables);
    }

  }

?>
