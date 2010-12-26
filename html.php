<?php

  function h($str) {
    return htmlspecialchars($str);
  }

  function options_to_attributes($options, $escape = true) {
    // TODO: escape
    $result = array();
    foreach ($options as $key => $value) {
      if (!isset($value)) continue;
      $result[] = $key . '="' . ($escape ? htmlspecialchars($value) : $value) . '"';
    }
    $result = join(' ', $result);
    return $result;
  }

  function tag($name, $options, $open = false, $escape = true) {
    $result = '';
    $result .= '<' . $name;
    if (!empty($options)) {
      $result .= ' ' . options_to_attributes($options, $escape);
    }
    $result .= $open ? '>' : ' />';
    return $result;
  }

  function content_tag($name, $content = '', $options = array(), $escape = true) {
    $result = '';
    $result .= '<' . $name;
    if (!empty($options)) {
      $result .= ' ' . options_to_attributes($options, $escape);
    }
    $result .= '>';
    $result .= $content;
    $result .= '</' . $name . '>';
    return $result;
  }

  function link_to($title, $url, $options = array()) {
    $options['href'] = $url;
    if ($options['confirm']) {
      $options['onclick'] = "return confirm('" . $options['confirm'] . "')";
      unset($options['confirm']);
    }
    return content_tag('a', $title, $options);
  }

  function image_tag($source, $options = array()) {
    $options['src'] = $source;
    if (!empty($options['size'])) {
      list($options['width'], $options['height']) = explode('x', $options['size']);
      unset($options['size']);
    }
    if (!empty($options['max_size'])) {
      list($max_width, $max_height) = explode('x', $options['max_size']);
      unset($options['max_size']);
      list($options['width'], $options['height']) = explode('x', normalize_image_size(PUBLIC_PATH . $source, $max_width, $max_height));
    }
    return tag('img', $options);
  }

  function normalize_image_size($image, $max_width, $max_height) {
    if (substr($image, -1, 1) == '/' or !file_exists($image)) {
      return;
    }

    $image_size = getimagesize($image);
    if (($image_size[0] / $image_size[1]) > ($max_width / $max_height)) {
      $width_attr = $max_width;
      $height_attr = round($max_width / $image_size[0] * $image_size[1]);
    } else {
      $width_attr = round($max_height / $image_size[1] * $image_size[0]);
      $height_attr = $max_height;
    }
    return $width_attr . 'x' . $height_attr;
  }

  function stylesheet_link_tag($sources = array()) {
    $result = array();
    if (is_string($sources)) {
      $sources = array($sources);
    }
    foreach ($sources as $source) {
      $result[] = '<link rel="stylesheet" type="text/css" href="/css/' . $source . '.css"/>';
    }
    $result = join("\n", $result);
    return $result;
  }

  function javascript_include_tag($sources = array()) {
    $result = array();
    if (is_string($sources)) {
      $sources = array($sources);
    }
    foreach ($sources as $source) {
      $result[] = '<script type="text/javascript" src="/js/' . $source . '.js"></script>';
    }
    $result = join("\n", $result);
    return $result;
  }

// Формы

  function form_tag($action, $body, $options = array()) {
    $options['action'] = $action;
    return content_tag('form', $body, $options);
  }

  function input_tag($type, $name, $value, $options) {
    $options['type'] = $type;
    $options['name'] = $name;
    $options['value'] = $value;
    return tag('input', $options);
  }

  function hidden_field_tag($name, $value = '', $options = array()) {
    return input_tag('hidden', $name, $value, $options);
  }

  function text_field_tag($name, $value = '', $options = array()) {
    return input_tag('text', $name, $value, $options);
  }

  function password_field_tag($name, $value = '', $options = array()) {
    return input_tag('password', $name, $value, $options);
  }

  function check_box_tag($name, $value = '', $checked = false, $options = array()) {
    if ($checked) {
      $options['checked'] = 'checked';
    }
    return input_tag('checkbox', $name, $value, $options);
  }

  function radio_button_tag($name, $value, $checked = false, $options = array()) {
    if ($checked) {
      $options['checked'] = 'checked';
    }
    return input_tag('radio', $name, $value, $options);
  }

  function select_tag($name, $option_tags = null, $options = array()) {
    $options['name'] = $name;
    return content_tag('select', $option_tags, $options);
  }

  function options_for_select($container = array(), $selected = null) {
    $result = '';
    foreach ($container as $key => $value) {
      $result .= content_tag('option', $value, array('value' => $key, 'selected' => ($key == $selected ? 'selected' : null)));
    }
    return $result;
  }

  function options_from_collection_for_select($collection, $value_field, $text_field, $selected = null) {
    $result = '';
    foreach ($collection as $item) {
      $result .= content_tag('option', $item->{$text_field}, array('value' => $item->{$value_field}, 'selected' => ($item->$value_field == $selected ? 'selected' : null)));
    }
    return $result;
  }

  function text_area_tag($name, $content = '', $options = array()) {
    if (!empty($options['size'])) {
      list($options['cols'], $options['rows']) = split('x', $options['size']);
      unset($options['size']);
    }
    $options['name'] = $name;
    return content_tag('textarea', $content, $options);
  }

  function file_field_tag($name, $options = array()) {
    $options['type'] = 'file';
    $options['name'] = $name;
    return tag('input', $options);
  }

  function submit_tag($value, $options = array()) {
    $options['type'] = 'submit';
    $options['value'] = $value;
    return tag('input', $options);
  }

  function image_submit_tag($source, $options = array()) {
    $options['type'] = 'image';
    $options['src'] = $source;
    return tag('input', $options);
  }

  function start_form_tag($action, $method = 'post', $options = array()) {
    $options['action'] = $action;
    $options['method'] = $method;
    return tag('form', $options, true);
  }

  function end_form_tag() {
    return '</form>';
  }

  function label_tag($title, $options = array()) {
    return content_tag('label', $title, $options);
  }


?>
