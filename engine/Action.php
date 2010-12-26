<?php
  class Action {
    protected $variables = array();

    /**
     * Метод открывает заданный буфер для текущего контроллера. После открытия буфера в него можно поместить код
     */
    protected function content_for($label = 'layout') {
      if (!isset($GLOBALS['output_buffer_stack_' . $this->controller])) {
        $GLOBALS['output_buffer_stack_' . $this->controller] = array();
      }
      array_push($GLOBALS['output_buffer_stack_' . $this->controller], $label);
      ob_start();
    }

    /**
     * Метод закрывает открытые буферы для текущего контроллера и сохраняет данные
     */
    protected function end_content_for() {
      $label = array_pop($GLOBALS['output_buffer_stack_' . $this->controller]);
      $GLOBALS['output_buffer_content_' . $this->controller][$label] .= ob_get_clean();
    }

    /**
     * Метод очищает заданный или все буферы для текущего контроллера
     */
    protected function empty_content_for($label = null) {
      if (!isset($label)) {
        foreach ($GLOBALS['output_buffer_content_' . $this->controller] as $key => $value) {
          unset($GLOBALS['output_buffer_content_' . $this->controller][$key]);
        }
      } else {
        unset($GLOBALS['output_buffer_content_' . $this->controller][$label]);
      }
    }

    /**
     * Метод возвращает содержимое заданного буфера для текущего контроллера
     */
    protected function yield($label = 'layout') {
      return $GLOBALS['output_buffer_content_' . $this->controller][$label];
    }

    /**
     * Идентификаия AJAX запроса
     */
    protected function is_xhr() {
      if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        return true;
      } else {
        return false;
      }
    }

    /**
     * Метод отдает в браузер файл
     */
    protected function send_file($path, $options = array()) {
      if (empty($options['filename'])) {
        $options['filename'] = basename($path);
      }

      if (empty($options['type'])) {
        $options['type'] = 'application/octet-stream';
      }

      if (empty($options['length'])) {
        $options['length'] = filesize($path);
      }

      if (empty($options['disposition'])) {
        $options['disposition'] = 'attachment';
      }

      header('Content-Type: ' . $options['type']);
      header('Content-Length: ' . $options['length']);
      header('Content-Disposition: ' . $options['disposition'] . '; filename="' . $options['filename'] . '"'); // FIXME: encode filename!
      header('Content-Transfer-Encoding: binary');

      $this->render_text(file_get_contents($path)); // FIXME: выводить заголовки согласно options
    }

    /**
     * Возращает "публичную" переменную
     */
    protected function get($name) {
      return $this->variables[$name];
    }

    /**
     * Устанавливает "публичную" переменную
     */
    protected function set($name, $value) {
      $this->variables[$name] = $value;
    }
  }
?>
