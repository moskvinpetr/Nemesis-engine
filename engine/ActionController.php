<?php
  class ActionController extends Action {
    protected $controller;
    protected $helpers = array();
    protected $action;
    protected $params = array();

    protected $layout;

    protected $layout_object;
    protected $template_object;

    protected $result = '';
    protected $rendered = false;

    protected $lang;

    public function __construct($params) {
      $this->params = $params;
      $this->controller = $this->params['controller'];
      $this->action = $this->params['action'];
      $this->set('params', $this->params);
    }

    /**
     * Метод запускает методы, предшествующие и последющие рендерингу а также запускает действие и возвращает результат
     */
    public function call_action() {
      $this->discover_helpers();
      foreach ($this->helpers as $helper) {
        $this->render_helper($helper);
      }

      $this->lang = array2object(Spyc::YAMLLoad(file_get_contents(ROOT_PATH . '/app/languages/ru.yaml')));

      if(!$this->before_action()) {
        return false;
      }

      if (method_exists($this, $this->action)) {
        $this->{$this->action}();
      } else {
        throw new Exception('Action is not found');
      }

      $this->after_action();

      if (!$this->rendered) {
        $this->render_action($this->action);
      }

      return $this->result;
    }

    /**
     * Находит все существующие helpers совпадающие с именами контроллеров
     */
    protected function discover_helpers() {
      foreach($this->get_parents_controllers(array(get_class($this))) as $parent) {
        $helper = substr(ActiveRecord\Inflector::instance()->uncamelize($parent), 0, -11) . '_helper';
        if(file_exists(ROOT_PATH . '/app/helpers/' . $helper . '.php')) {
          $this->helpers[] = $helper;
        }
      }
    }

    /**
     * Ищет списки всех названий контроллеров, участвующих в наследовании
     */
    public function get_parents_controllers($plist=array(), $class=null) {
      $class = $class ? $class : $this;
      $parent = get_parent_class($class);
      if($parent) {
        $plist[] = $parent;
        $plist = self::get_parents_controllers($plist, $parent);
      }
      return $plist;
    }

    /**
     * Подключает helpers
     */
    protected function render_helper($helper) {
      include_once(ROOT_PATH . '/app/helpers/' . $helper . '.php');
    }

    /**
     * Прототип метода, запускаемого до действия. Предполагается, что ее необходимо будет наследовать в контроллере. Для успешного продолжения нужно возвращать истину
     */
    protected function before_action() {
      return true;
    }

    /**
     * Прототип метода, запускаемого после действия. Предполагается, что ее необходимо будет наследовать в контроллере. Для успешного продолжения нужно возвращать истину
     */
    protected function after_action() {
      return true;
    }

    /**
     * Вывод действия
     */
    protected function render_action($action, $options = array()) {
      if ($this->rendered) throw new Exception('Double render error');
      $path = ROOT_PATH . '/app/views';
      if (strpos($action, '/') === false) {
        $path .= '/' . $this->controller;
      }
      $path .= '/' . $action . '.php';

      $content_for_layout = $this->render_template($path);

      $this->set('content_for_layout', $content_for_layout);

      $this->content_for();
      echo $content_for_layout;
      $this->end_content_for();

      if ($this->layout && !(!empty($options['layout']) && $options['layout'] == false)) {
        $this->result = $this->render_template(ROOT_PATH . '/app/views/layouts/' . $this->layout . '.php');
      } else {
        echo yield();
      }

      $this->rendered = true;
    }

    /**
     * Метод возвращает правильный путь для чтения партиала
     */
    protected function partial_path($filename) {
      $parts = split('/', $filename);
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
      $path = join('/', $parts);
      return $path;
    }

    /**
     * Метод выводит партиал (если партиал лежит в тойже папке, что и действие, достаточно указать его имя без подчеркивания)
     */
    protected function render_partial($filename, $options = array()) {
      // FIXME: написать нормальную функцию, совместить с view
      $path = $this->partial_path($filename);
      echo $this->render_template($path, $options);

      $this->rendered = true;
    }

    /**
     * Вывод текста
     */
    protected function render_text($text, $options = array()) {
      echo $text;
      $this->rendered = true;
    }

    /**
     * Вывод шаблона
     */
    protected function render_template($filename, $options = array()) {
      if (!file_exists($filename)) {
        throw new Exception('Missing template "' . $filename . '"!');
      }
      extract(array_merge($this->variables, $options));
      ob_start();
      include($filename);
      return ob_get_clean();
    }

    /**
     * Создание редиректа
     */
    protected function redirect_to($url, $status = 302) {
      $this->rendered = true;
      switch ($status) {
      case 301 :
        header('HTTP/1.1 301 Moved Permanently');
        break;
      case 302 :
        header('HTTP/1.1 302 Found');
        break;
      case 303 :
        header('HTTP/1.1 303 See Other');
        break;
      case 401 :
        header('HTTP/1.1 401 Unauthorized');
      case 403 :
        header('HTTP/1.1 403 Forbidden');
      }
      header('Location: ' . $url);
    }

    /**
     * Возвращает ссылку в соотвествии с routes
     */
    protected function url_for($options = array()) {
      if (empty($options['controller'])) {
        $options['controller'] = $this->controller;
        if (empty($options['action'])) {
          $options['action'] = $this->action;
        }
      } else {
        if (empty($options['action'])) {
          $options['action'] = 'index';
        }
      }
      $router = Router::get_instance();
      return $router->get_url_by_params($options);
    }

    protected function set_title($title) {
      $content_for_title = $this->get('content_for_title');
      $this->set('content_for_title', $content_for_title . $title);
    }
  }

?>
