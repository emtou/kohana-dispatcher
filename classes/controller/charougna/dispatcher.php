<?php
/**
 * Declares Charougna_Dispatcher controller
 *
 * PHP version 5
 *
 * @group dispatcher
 *
 * @category  Dispatcher
 * @package   Dispatcher
 * @author    mtou <mtou@charougna.com>
 * @copyright 2011 mtou
 * @license   http://www.debian.org/misc/bsd.license BSD License (3 Clause)
 * @link      https://github.com/emtou/kohana-dispatcher/tree/master/classes/charougna/dispatcher.php
 */

defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Provides Charougna_Dispatcher controller
 *
 * PHP version 5
 *
 * @group dispatcher
 *
 * @category  Dispatcher
 * @package   Dispatcher
 * @author    mtou <mtou@charougna.com>
 * @copyright 2011 mtou
 * @license   http://www.debian.org/misc/bsd.license BSD License (3 Clause)
 * @link      https://github.com/emtou/kohana-dispatcher/tree/master/classes/charougna/dispatcher.php
 */
class Controller_Charougna_Dispatcher extends Controller
{
  const METHOD_UNKNOWN   = 0;
  const METHOD_GET       = 1;
  const METHOD_POST      = 2;
  const METHOD_AJAX_GET  = 3;
  const METHOD_AJAX_POST = 4;

  protected $_dispatcher_actions = array();


  /**
   * Check if the action is configured and is allowed to be called with this method
   *
   * @param string $action_name Action name
   *
   * @return bool
   *
   * @throws Kohana_Exception Action «X» is not configured.
   * @throws Kohana_Exception Unknown calling method for action «X».
   */
  protected function _can_perform_action($action_name)
  {
    if ( ! $this->_is_action_configured($action_name))
    {
      throw new Kohana_Exception('Action «'.$action_name.'» is not configured.');
    }

    if (in_array($this->_get_method(), $this->_dispatcher_actions[$action_name]))
    {
      return TRUE;
    }

    throw new Kohana_Exception('Unknown calling method for action «'.$action_name.'».');
  }


  /**
   * Configure an action with a set of allowed methods
   *
   * @param string $action_name     Action name
   * @param array  $allowed_methods Methods
   *
   * @return null
   */
  protected function _configure_action($action_name, array $allowed_methods)
  {
    $this->_dispatcher_actions[$action_name] = $allowed_methods;
  }


  /**
   * Do an action (called from an action method)
   *
   * @param array &$params Optional parameters
   *
   * @return bool
   *
   * @throws Kohana_Exception Can't perform action : X
   * @throws Kohana_Exception Can't perform action
   */
  protected function _do_action(array & $params = NULL)
  {
    $action_name = $this->_extract_action_name();

    try
    {
      if ( ! $this->_can_perform_action($action_name))
        return FALSE;
    }
    catch (Exception $exception)
    {
      throw new Kohana_Exception('Can\'t perform action : '.$exception->getMessage());
    }


    // Init
    $init_method_name = '_action_'.$action_name.'_init';
    if (method_exists($this, $init_method_name))
    {
      $this->$init_method_name($params);
    }
    $init_method_name = '_action_'.$action_name.'_init_'.$this->_get_method_name();
    if (method_exists($this, $init_method_name))
    {
      $this->$init_method_name($params);
    }

    // Perform
    $perform_method_name = '_action_'.$action_name.'_'.$this->_get_method_name();
    if (method_exists($this, $perform_method_name))
    {
      $this->$perform_method_name($params);
    }
    else
    {
      throw new Kohana_Exception('Can\'t perform action.');
    }

    // End
    $end_method_name = '_action_'.$action_name.'_end';
    if (method_exists($this, $end_method_name))
    {
      $this->$end_method_name($params);
    }
    $end_method_name = '_action_'.$action_name.'_end_'.$this->_get_method_name();
    if (method_exists($this, $end_method_name))
    {
      $this->$end_method_name($params);
    }
  }


  /**
   * Extracts the action name
   *
   * @return string action name or NO_ACTION
   */
  protected function _extract_action_name()
  {
    try
    {
      $backtrace = debug_backtrace();

      foreach ($backtrace as $level)
      {
        if (preg_match('/^action_/', $level['function']))
          return substr($level['function'], 7);
      }

      throw new Kohana_Exception('Can\'t extract action name');
    }
    catch (Exception $exception)
    {
      unset($exception);
      return 'NO_ACTION';
    }
  }


  /**
   * Find configured actions for a given action
   *
   * @param string $action_name action name
   *
   * @return array
   */
  protected function _get_action_methods($action_name)
  {
    if ( ! $this->_is_action_configured($action_name))
    {
      return array();
    }

    return $this->_dispatcher_actions[$action_name];
  }


  /**
   * Find calling method
   *
   * @return int constant METHOD_*
   */
  protected function _get_method()
  {
    if ($this->request->method() == 'GET')
      return $this::METHOD_GET;

    if ($this->request->method() == 'POST')
        return $this::METHOD_POST;

    if ($this->request->is_ajax())
    {
      if ($this->request->method() == 'GET')
        return $this::METHOD_AJAX_GET;

      if ($this->request->method() == 'POST')
        return $this::METHOD_AJAX_POST;
    }

    return $this::METHOD_UNKNOWN;
  }


  /**
   * Find calling method name
   *
   * @param Controller_Dispatch::METHOD_* $method optional method
   *
   * @return string
   */
  protected function _get_method_name($method = NULL)
  {
    if ($method == NULL)
    {
      $method = $this->_get_method();
    }

    switch ($method)
    {
      case $this::METHOD_GET :
        return 'get';
      case $this::METHOD_POST :
        return 'post';
      case $this::METHOD_AJAX_GET :
        return 'ajax_get';
      case $this::METHOD_AJAX_POST :
        return 'ajax_post';
    }

    return 'unknown';
  }


  /**
   * Check if the action is configured
   *
   * @param string $action_name Action name
   *
   * @return bool
   */
  protected function _is_action_configured($action_name)
  {
    if (array_key_exists($action_name, $this->_dispatcher_actions))
    {
      return TRUE;
    }

    return FALSE;
  }

} // End class Charougna_Dispatcher