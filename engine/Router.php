<?php

  class Router {
    static private $instance = null;

    static function get_instance() {
      if (self::$instance == null) {
        self::$instance = new Router();
      }
      return self::$instance;
    }

    private function __construct() {
    }

    private function __clone() {
    }

    private $routes = array();

    public function connect($url, $options = array()) {
      /* :controller, :action, :id, *path */
      $route = array('url' => $url, 'options' => $options, 'keys' => array(), 'requirements' => array(), 'original_url' => $url);
      if (!empty($route['options']['requirements'])) {
        $route['requirements'] = $route['options']['requirements'];
        unset($route['options']['requirements']);
      }
      if (preg_match_all('/([:\*]([a-z][a-z0-9_-]*))/', $url, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $key => $match) {
          if (!empty($route['requirements'][$match[2]])) {
            // FIXME: а если в requirements есть скобки?
            $replace = '(' . $route['requirements'][$match[2]] . ')';
          } elseif (substr($match[1], 0, 1) == '*') {
            $replace =  '(.+)';
          } else {
            $replace =  '([^/]+)';
          }
          $route['url'] = str_replace($match[1], $replace, $route['url']);
          if (!isset($route['options'][$match[2]])) {
            $route['keys'][$match[1]] = $key + 1;
          }
        }
      }
      $this->routes[] = $route;
    }

    /**
     * Метод формирует стразу 4 роута: index, add, edit/:id, delete/:id
     */
    public function connect_admin($url, $options) {
      $this->connect($url, array_merge($options, array('action' => 'index')));
      $this->connect($url . '/add', array_merge($options, array('action' => 'add')));
      $this->connect($url . '/edit/:id', array_merge($options, array('action' => 'edit')));
      $this->connect($url . '/delete/:id', array_merge($options, array('action' => 'delete')));
    }

    public function get_params_by_url($url) {
      $params = array();
      foreach ($this->routes as $route) {
        if (preg_match('/^' . str_replace('/', '\/', $route['url']) . '$/', trim($url, '/'), $matches)) {
          foreach ($route['keys'] as $key => $value) {
            $params[substr($key, 1)] = $matches[$value];
            if (substr($key, 0, 1) == '*') {
              $params[substr($key, 1)] = split('/', $params[substr($key, 1)]);
            }
          }
          foreach ($route['options'] as $key => $value) {
            $params[$key] = $value;
          }
          return $params;
        }
      }
      if (!isset($params['controller']) or !isset($params['action'])) {
        throw new Exception('no_action_found');
      }
    }

    public function get_url_by_params($params) {
      //print_r($params);

      // вычищение пустых параметров (FIXME: спорно, продумать)
      foreach ($params as $key => $value) {
        if (empty($value)) {
          unset($params[$key]);
        }
      }

      // поиск соответсвующего route
      foreach ($this->routes as $route) {
        //print_r($route);

        $route_match = true;

        // проверка на соответствие явно заданных параметров
        foreach ($route['options'] as $key => $value) {
          if (empty($params[$key]) or $params[$key] != $route['options'][$key]) {
            $route_match = false;
            break;
          }
        }

        if (!$route_match) {
          continue;
        }

        // проверка на соответствие подставляемых параметров
        foreach ($route['keys'] as $key => $value) {
          // FIXME: по-разному проверять *key и :key, проверять requirements
          $param_name = substr($key, 1);
          if (empty($params[$param_name]) or (!empty($route['requirements'][$param_name]) and !preg_match('/' . $route['requirements'][$param_name] . '/', $params[$param_name]))) {
            $route_match = false;
            break;
          }
        }

        if (!$route_match) {
          continue;
        }

        $url = '/' . $route['original_url'];
        $query = '';
        foreach ($params as $param_name => $param_value) {
          if ($key = ':' . $param_name and !empty($route['keys'][$key])) {
            $url = str_replace($key, $param_value, $url);
          } elseif ($key = '*' . $param_name and !empty($route['keys'][$key])) {
            $url = str_replace($key, join('/', $param_value), $url); // FIXME: urlencode $param_value
          } elseif (!empty($route['options'][$param_name])) {
            // nothing
          } else {
            if (is_array($param_value)) {
              $param_value = join('/', $param_value);
            }
            $query .= '&' . urlencode($param_name) . '=' . urlencode($param_value);
          }
        }

        if (!empty($query)) {
          $url .= '?' . substr($query, 1);
        }


        return $url;
      }

      throw new Exception('No route found');
    }

  }

?>
