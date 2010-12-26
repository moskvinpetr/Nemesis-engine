<?php

  require_once('htmlMimeMail5/htmlMimeMail5.php');

  class ActionMailer {

    protected $action;
    protected $variables = array();

    protected $recipients = array();
    protected $cc = array();
    protected $bcc = array();

    protected $subject = '';
    protected $from = '';

    protected $attachments = array();
    protected $embedded_images = array();
    protected $headers = array();

    protected $host = ''; // TODO: default_url_options

    static $helpers = array();
    protected $all_helpers = array();
    protected $helper_objects = array();

    public function __construct($params = array()) {
      $this->layout = $params['layout'];
      $this->action = $params['action'];
    }

    /**
    * Возвращает пользовательские helpers
    *
    * @return array()
    */
    protected function helpers() {
      return array('application_helper', 'html_helper', 'form_tag_helper', 'form_helper', 'cabinets_helper', 'products_helper');
    }

    protected function get($name) {
      return $this->variables[$name];
    }

    protected function set($name, $value) {
      $this->variables[$name] = $value;
    }

    public function add_embedded_image($filename, $content_type = 'application/octet-stream') {
      $this->embedded_images[] = array('filename' => $filename, 'content_type' => $content_type);

      return $this;
    }

    public function add_attachment($filename, $content_type = 'application/octet-stream', $data = null) {

      $this->attachments[] = array('filename' => $filename, 'content_type' => $content_type, 'data' => $data);

      return $this;
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
      $path = join(DS, $parts);
      return $path;
    }

    protected function render_partial($filename, $options = array()) {
      // FIXME: написать нормальную функцию, совместить с view
      $path = $this->partial_path($filename);
      return $this->render_template($path, $options);
    }

    /*protected function url_for($options = array()) {
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
      if (empty($options['host'])) {
        $options['host'] = $this->host;
      }
      return url_for($options);
    }*/

    static public function send_mail($to, $subject, $message, $additional_headers = null, $additional_parameters = null) {
      if (FRAMEWORK_ENV == 'development') {
        echo "Sent mail:\n";
        echo "To: $to\n";
        echo "Subject: $subject\n";
        echo "$message\n";
      } else {
        mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $additional_headers, $additional_parameters);
      }
    }

    /*static public function __callStatic($name, $arguments) {
      // TODO: нужен late static binding
      $class = get_called_class();
      if (substr($name, 0, 7) == 'deliver') {
        $mailer = new $class();
        return $mailer->{substr($name, 7)}($arguments);
      }
    }*/

    /**
    Создает объект-потомок ActionMailer и запускает заданный action для подготовки сообщения
    @param string $name имя action
    */

    static public function create($name) {
      //$class = get_called_class();
      // FIXME находить реальный класс
      $mailer = new Notifier(array('action' => $name, 'layout' => 'notifications'));
      $arguments = func_get_args();
      call_user_func_array(array(&$mailer, $name), array_slice($arguments, 1));
      return $mailer;
    }

    /**
    Отправляет подготовленное сообщение
    */

    public function deliver() {
      $this->collect_custom_helpers();
      $this->discover_helpers();
      $this->instantiate_helpers();

      $this->empty_content_for();
      $this->content_for();
        echo $this->render_template(ROOT_PATH . '/app/views/notifications/' . $this->action . '.php');
      $this->end_content_for();

      if($this->layout) {
        $message = $this->render_template(ROOT_PATH . DS . 'app' . DS . 'views' . DS . 'layouts' . DS . $this->layout . '.php');
      } else {
        $message = yield();
      }

      $this->email_keeper($message);

      $htmlMimeMail = new htmlMimeMail5();

      $htmlMimeMail->setHeadCharset('UTF-8');
      $htmlMimeMail->setTextCharset('UTF-8');
      $htmlMimeMail->setHTMLCharset('UTF-8');

      $htmlMimeMail->setSubject($this->subject);

      foreach ($this->embedded_images as $embedded_image) {
        $htmlMimeMail->addEmbeddedImage(new fileEmbeddedImage($embedded_image['filename'], $embedded_image['content_type']));
      }

      foreach ($this->attachments as $attachment) {
        if (!empty($attachment['data'])) {
          $htmlMimeMail->addAttachment(new stringAttachment($attachment['data'], $attachment['filename'], $attachment['content_type']));
        } else {
          $htmlMimeMail->addAttachment(new fileAttachment($attachment['filename'], $attachment['content_type']));
        }
      }

      $htmlMimeMail->setHTML($message, PUBLIC_PATH);
      /*if ($this->attributes['html']) {
        $htmlMimeMail->setHTML($this->attributes['html']);
      } else {
        $htmlMimeMail->setText($this->attributes['text']);
      }*/

      if ($this->from) {
        $htmlMimeMail->setFrom($this->from);
      }

      if ($this->cc) {
        $htmlMimeMail->setCc(join(', ', $this->cc));
      }

      if ($this->bcc) {
        $htmlMimeMail->setBcc(join(', ', $this->bcc));
      }

      foreach ($this->headers as $name => $value) {
        $htmlMimeMail->setHeader($name, $value);
      }

      $result = $htmlMimeMail->send($this->recipients);

      Logger::debug("Sent mail:\n" . $htmlMimeMail->getRFC822($this->recipients));

      return $result;
    }

    public function email_keeper($message) {
      // NOTE: Этот метод ущербен по самой своей ущербной сущности! Надо выводить текст реальных писем, а не якобы то же самое, и тогда это будет честная проверка функционала, а не очковтирательский костыль!
      if (FRAMEWORK_ENV == 'development') {

        $headers = array(
          'Time' => date('Y-m-d H:i:s'),
          'Subject' => $this->subject,
          'From' => $this->from,
          'To' => join(', ', $this->recipients),
          'BCC' => join(', ', $this->bcc),
          'CC' => join(', ', $this->cc)
        );
        $content .= '<pre>';
        foreach($headers as $header => $value) {
          if($value) {
            $content .= $header . ': ' . $value . "\n";
          }
        }
        $content .= '</pre><br>' . $message;

        $file_name = self::find_email_keeper_filename(ROOT_PATH . '/logs/email_keeper/' . date('Y-m-d'));

        file_put_contents($file_name . '.html', $content);
      }
    }

    static public function find_email_keeper_filename($file_name, $n = 0) {
       if($n > 0) $suffix = '-' . $n;
       if(file_exists($file_name . $suffix . '.html')) {
         $new_name = self::find_email_keeper_filename($file_name, ($n + 1));
       } else {
         $new_name = $file_name . $suffix;
       }
       return $new_name;
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
        $helper = substr(Inflection::underscore($parent), 0, -7) . '_helper';
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
