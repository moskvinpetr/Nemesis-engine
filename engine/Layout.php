<?php

  class Layout extends Template {

    protected $layout;

    function __construct($layout, $variables) {
      $this->layout = $layout;
      $filename = ROOT_PATH . DS . 'app' . DS . 'views' . DS . 'layouts' . DS . $this->layout . '.php';
      if (!file_exists($filename)) {
        $filename = ROOT_PATH . DS . 'app' . DS . 'views' . DS . 'layouts' . DS . 'application.php';
      }
      parent::__construct($filename, $variables);
    }

  }

?>
