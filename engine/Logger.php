<?php

  class Logger {

    static private $instance = null;

    static function get_instance() {
      if (self::$instance == null) {
        self::$instance = new Logger();
      }
      return self::$instance;
    }

    protected $log_handler;

    private function __construct() {
    }

    private function __clone() {
    }

    static function debug($message) {
      $logger = self::get_instance();
      $logger->debug_message($message);
    }

    public function debug_message($message) {
      if(DEVELOPMENT) {
        $this->save_message($message);
      }
    }

    protected function save_message($message) {
      if (empty($GLOBALS['app_log'])) {
        $GLOBALS['app_log'] = array();
      }
      $entry = date('[Y-m-d H:i:s]') . ' ' . $message;
      $GLOBALS['app_log'][] = $entry;
      $log_handler = fopen(ROOT_PATH . '/logs/debug.log', 'a');
      fputs($log_handler, $entry . "\n");
      fclose($log_handler);
    }
  }

?>
