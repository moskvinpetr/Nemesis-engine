<?php

  class ActionHelper {

    protected $controller_object;

    public function __construct($controller) {
      $this->controller_object = $controller;
    }

    public function __call($name, $arguments) {
      return $this->controller_object->proxy_method($name, $arguments);
    }

    public function __get($name) {
      return $this->controller_object->proxy_attr($name);
    }

  }

?>
