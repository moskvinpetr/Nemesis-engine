<?php

  class Template {

    protected $filename;
    protected $variables;

    function __construct($filename, $variables = array(), $controller = null) {
      $this->filename = $filename;
      $this->variables = $variables;
      $this->controller = $controller;
    }

    public function render() {
      // TODO: exception handling
      extract($this->variables);
      if (file_exists($this->filename)) {
        ob_start();
        include($this->filename);
        return ob_get_clean();
      } else {
        throw new Exception('missing_template');
      }
    }

    public function __get($name) {
      return $this->controller->$name;
    }

    public function __call($name, $args) {
      return $this->controller->$name(array(__CLASS__, $name), $args);
    }
  }

?>
