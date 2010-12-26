<?php

  class ActionController {

    protected $controller = 'application';
    protected $action = 'index';
    protected $params = array();

    protected $variables = array();

    protected $layout = 'application';

    protected $layout_object;
    protected $template_object;

    protected $result = '';
    protected $rendered = false;

    static $helpers = array();
    protected $all_helpers = array();
    protected $helper_objects = array();

    public function __construct($params) {
      $this->params = $params;
      $this->controller = $this->params['controller'];
      $this->action = $this->params['action'];
      $this->set('params', $this->params);
      // TODO: set layout with fallback (array() или сразу проверять на exists?)
    }

    /**
    * Возвращает пользовательские helpers
    *
    * @return array()
    */
    protected function helpers() {
      return array('html_helper', 'form_tag_helper', 'form_helper', 'cabinets_helper', 'products_helper');
    }

    public function call_action() {
      $this->collect_custom_helpers();
      $this->discover_helpers();
      $this->instantiate_helpers();

      if (!$this->before_filter()) {
        return false; // FIXME: что возвращать?
      }

      if (!in_array($this->action, array('proxy_method', 'proxy_attr')) && method_exists($this, $this->action)) {
        $this->{$this->action}();
      } else {
        throw new Exception('Action does not exist or is reserved');
      }

      $this->after_filter();

      if (!$this->rendered) {
        $this->render(array('action' => $this->action));
      }

      $this->clean_flash(); // FIXME: починить механизм flash

      return $this->result;
    }

    protected function before_filter() {
      return true;
    }

    protected function after_filter() {
      return true;
    }

    protected function get_flash($key) {
      return $_SESSION['flash'][$key];
    }

    protected function set_flash($key, $value) {
      $_SESSION['flash'][$key] = $value;
    }

    protected function clean_flash() {
      unset($_SESSION['flash']);
    }

    protected function get($name) {
      return $this->variables[$name];
    }

    protected function set($name, $value) {
      $this->variables[$name] = $value;
    }

    protected function render($options = array()) {
      if (!empty($options['action'])) {
        $this->render_action($options['action'], $options);
      } elseif (!empty($options['partial'])) {
        $this->render_partial($options['partial'], $options);
      }
    }

    protected function send_file($path, $options = array()) {
      if (empty($options['filename'])) {
        $options['filename'] = basename($path);
      }

      /*$mime_types = array(
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
      );*/

      if (empty($options['type'])) {
        /*if (!empty($options['filename']) and !empty($mime_types[pathinfo($options['filename'], PATHINFO_EXTENSION)])) {
          $options['type'] = $mime_types[pathinfo($options['filename'], PATHINFO_EXTENSION)];
        } else {
          $options['type'] = 'application/octet-stream';
          //$options['type'] = 'application/x-pdf';
        }*/
        $options['type'] = 'application/octet-stream';
      }

      if (empty($options['length'])) {
        $options['length'] = filesize($path);
      }

      if (empty($options['disposition'])) {
        $options['disposition'] = 'attachment';
      }

      // :stream, :buffer_size, :status

      header('Content-Type: ' . $options['type']);
      header('Content-Length: ' . $options['length']);
      header('Content-Disposition: ' . $options['disposition'] . '; filename="' . $options['filename'] . '"'); // FIXME: encode filename!
      header('Content-Transfer-Encoding: binary');

      //$this->render_text(file_get_contents($path)); // FIXME: выводить заголовки согласно options
      readfile($path);
      $this->rendered = true;
    }

    protected function render_action($action, $options = array()) {
      if ($this->rendered) throw new Exception('Double render error');

      $path = ROOT_PATH . DS . 'app' . DS . 'views';
      if (strpos($action, '/') === false) {
        $path .= DS . $this->controller;
      }
      $path .= DS . $action . '.php';

      $content_for_layout = $this->render_template($path);

      $this->set('content_for_layout', $content_for_layout);

      $this->content_for();
      echo $content_for_layout;
      $this->end_content_for();

      if ($this->layout and !(!empty($options['layout']) and $options['layout'] == false)) {
        $this->result = $this->render_template(ROOT_PATH . DS . 'app' . DS . 'views' . DS . 'layouts' . DS . $this->layout . '.php');
      } else {
        echo yield();
      }

      $this->rendered = true;
    }

    protected function partial_path($filename) {
      $parts = explode('/', $filename);
      if (count($parts) <= 1) {
        array_unshift($parts, $this->controller);
      }
      array_unshift($parts, ROOT_PATH, 'app', 'views');
      if (substr($parts[count($parts) - 1], -4, 4) != '.php') {
        $parts[count($parts) - 1] = $parts[count($parts) - 1] . '.php';
      }
      if (substr($parts[count($parts) - 1], 0, 1) != '_') {
        $parts[count($parts) - 1] = '_' . $parts[count($parts) - 1];
      }
      $path = join(DS, $parts);
      return $path;
    }

    protected function render_partial($filename, $options = array()) {
      // FIXME: написать нормальную функцию, совместить с view
      $path = $this->partial_path($filename);
      echo $this->render_template($path, $options);

      $this->rendered = true;
    }

    protected function render_text($text, $options = array()) {
      echo $text;
      $this->rendered = true;
    }

    protected function render_template($filename, $options = array()) {
      if (!file_exists($filename)) {
        throw new Exception('Missing template "' . $filename . '"!');
      }
      extract(array_merge($this->variables, $options));
      ob_start();
      include($filename);
      return ob_get_clean();
    }

    protected function redirect_to($url, $options = array()) {
      $this->rendered = true;
      if (!$options['status']) {
        $options['status'] = 302;
      }
      switch ($options['status']) {
      case 301:
        header('HTTP/1.1 301 Moved Permanently');
        break;
      case 302:
        header('HTTP/1.1 302 Found');
        break;
      case 303:
        header('HTTP/1.1 303 See Other');
        break;
      }
      header('Location: ' . $url);
    }

    protected function is_xhr() {
      if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        return true;
      } else {
        return false;
      }
    }

    public function proxy_method($name, $arguments) {
      return call_user_func_array(array(&$this, $name), $arguments);
    }

    public function proxy_attr($name) {
      return $this->$name;
    }

    /**
     * Собирает пользовательские helpers
     **/
    protected function collect_custom_helpers() {
      $this->all_helpers = array_merge($this->all_helpers, $this->helpers());
    }

    /**
     * Находит все существующие helpers совпадающие с именами контроллеров
     **/
    protected function discover_helpers() {
      foreach($this->get_parents(array(get_class($this))) as $parent) {
        $helper = substr(Inflection::underscore($parent), 0, -11) . '_helper';
        if(file_exists(ROOT_PATH . DS . 'app' . DS . 'helpers' . DS . $helper . '.php')) {
          $this->all_helpers[] = $helper;
        }
      }
    }

    /**
     ** Создает экземпляры helpers
     **/
    protected function instantiate_helpers() {
      foreach ($this->all_helpers as $helper) {
        $class_name = Inflection::camelize($helper);
        $this->helper_objects[$helper] = new $class_name($this);
      }
    }

    /**
     * Подключает хелперы
     */
    protected function load_helper($helpers) {
      if(!is_array($helpers)) $helpers = array($helpers);

      foreach ($helpers as $helper) {
        $helper .= '_helper';
        $class_name = Inflection::camelize($helper);
        $this->helper_objects[$helper] = new $class_name($this);
      }
    }

    /**
    * Ищет списки всех названий контроллеров, участвующих в наследовании
    *
    * @param array $plist - Текущий список названий контроллеров
    * @param mixed $class - Имя или экземпляр класса
    * @return array
    */
    public function get_parents($plist=array(), $class=null) {
      $class = $class ? $class : $this;
      $parent = get_parent_class($class);
      if($parent) {
        $plist[] = $parent;
        $plist = self::get_parents($plist, $parent);
      }
      return $plist;
    }

    public function __call($name, $arguments) {
      foreach ($this->helper_objects as $helper) {
        if (method_exists($helper, $name)) {
          return call_user_func_array(array(&$helper, $name), $arguments);
        }
      }
    }

  }

?>
