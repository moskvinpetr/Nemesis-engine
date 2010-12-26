<?php
  /**
   * Функция автоматически подключает классы
   * @param string $class_name Имя класса
   * @return bool
   * @throws Exception Если файл не найден
   */
  function autoload($class_name) {
    $class_name_camelize = first_letter_to_upper_case(ActiveRecord\Inflector::instance()->camelize($class_name));
    $class_name_uncamelize = ActiveRecord\Inflector::instance()->uncamelize($class_name);

    $dirs = array(
      ROOT_PATH . '/vendor/engine',
      ROOT_PATH . '/app/controllers',
      ROOT_PATH . '/app/models',
    );
    foreach ($dirs as $dir) {
      if (file_exists($dir . '/' . $class_name_camelize . '.php')) {
        include_once($dir . '/' . $class_name_camelize . '.php');
        return;
      } elseif (file_exists($dir . '/' . $class_name_uncamelize . '.php')) {
        include_once($dir . '/' . $class_name_uncamelize . '.php');
        return;
      }
    }
    throw new Exception('Missing class ' . $class_name);
  }

  function first_letter_to_upper_case($str) {
    return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
  }

  function breadcrumbs($breadcrumbs, $separator = ' / ') {
    $result = array();
    while (!empty($breadcrumbs)) {
      $breadcrumb = array_shift($breadcrumbs);
      list($title, $url) = $breadcrumb;
      if (!empty($breadcrumbs)) {
        $result[] = link_to($title, $url);
      } else {
        $result[] = $title;
      }
    }
    $result = join($separator, $result);
    return $result;
  }

  function human_date_format($date) {
    return strftime('%d.%m.%Y', strtotime($date));
  }

  function human_datetime_format($datetime) {
    return strftime('%d.%m.%Y, %H:%M', strtotime($datetime));
  }

  function get_size($file_name){
    $size = filesize($file_name);
    $exp = 1;
    while($size >= pow(1024, $exp)) $exp++;
    $ext = array(' байт',' Кб',' Мб');
    $summary = round(($size * 100) / pow(1024, ($exp - 1))) / 100;
    $strsize = sprintf("%.2f%s", $summary, $ext[$exp - 1]);
    return $strsize;
  }

  function array2object($data) {
    if(!is_array($data)) return $data;

    $object = new stdClass();
    if (is_array($data) && count($data) > 0) {
        foreach ($data as $name=>$value) {
            $name = strtolower(trim($name));
            if (!empty($name)) {
                $object->$name = array2object($value);
            }
        }
    }
    return $object;
  }
  function printr($array) {
    echo '<pre>';
    print_r($array);
    echo '</pre>';
  }
?>