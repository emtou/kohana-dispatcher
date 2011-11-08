<?php
/**
 * Declares Dispatcher_Core
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
 * @link      https://github.com/emtou/kohana-dispatcher/tree/master/classes/dispatcher/core.php
 */

defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Provides Dispatcher_Core
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
 * @link      https://github.com/emtou/kohana-dispatcher/tree/master/classes/dispatcher/core.php
 */
abstract class Dispatcher_Core extends Controller
{
  const METHOD_UNKNOWN   = 0;
  const METHOD_GET       = 1;
  const METHOD_POST      = 2;
  const METHOD_AJAX_GET  = 3;
  const METHOD_AJAX_POST = 4;

  protected $_action_classnames  = array(); /** list of Dispatcher_Action class names */
  protected $_action_instances   = array(); /** list of Dispatcher_Action instances */
  protected $_dispatcher_actions = array();


  /**
   * Call specific action method
   *
   * @param string $name    name of the action
   * @param method $phase   name of the action's phase
   * @param method $method  name of the action's method
   * @param array  &$params Optional action parameters
   *
   * @return bool continue action process ?
   *
   * @todo remove controler's method name HACK
   */
  protected function _call_action($name, $phase, $method, array & $params = NULL)
  {
    $controler_methodname = '_action_'.$name;
    // @hack : old API method naming ('main' and 'global' stripped)
    $controler_methodname .= (($phase != 'main')?('_'.$phase):'');
    $controler_methodname .= (($method != 'global')?('_'.$method):'');

    $action_instance = $this->_get_action_instance($name);
    if ($action_instance instanceof Dispatcher_Action
        and $action_instance->perform($phase, $method, $params) === FALSE)
    {
      return FALSE;
    }
    elseif (method_exists($this, $controler_methodname)
        and $this->{$controler_methodname}($params) === FALSE)
    {
      return FALSE;
    }

    return TRUE;
  }


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
   * @param string $action_name      Action name
   * @param array  $allowed_methods  Methods
   * @param string $action_classname Optional Dispatcher_Action class name
   *
   * @return null
   */
  protected function _configure_action($action_name, array $allowed_methods, $action_classname = '')
  {
    if ($action_classname != '')
    {
      $this->_action_classnames[$action_name] = $action_classname;
    }
    $this->_dispatcher_actions[$action_name] = $allowed_methods;
  }


  /**
   * Do an action (called from an action method)
   *
   * @param array &$params Optional parameters
   *
   * @return bool
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

    if ($this->_do_action_init($action_name, $params) === FALSE)
      return;

    if ($this->_do_action_perform($action_name, $params) === FALSE)
      return;

    $this->_do_action_end($action_name, $params);
  }


  /**
   * Call action end methods (both global and specific to current method)
   *
   * @param string $action_name name of the action
   * @param array  &$params     optional action parameters
   *
   * @return bool continue action process ?
   */
  protected function _do_action_end($action_name, array & $params = NULL)
  {
    if ($this->_call_action($action_name, 'end', 'global', $params) === FALSE)
      return FALSE;

    if ($this->_call_action($action_name, 'end', $this->_get_method_name(), $params) === FALSE)
      return FALSE;

    return TRUE;
  }


  /**
   * Call action init methods (both global and specific to current method)
   *
   * @param string $action_name name of the action
   * @param array  &$params     optional action parameters
   *
   * @return bool continue action process ?
   */
  protected function _do_action_init($action_name, array & $params = NULL)
  {

    if ($this->_call_action($action_name, 'init', 'global', $params) === FALSE)
      return FALSE;

    if ($this->_call_action($action_name, 'init', $this->_get_method_name(), $params) === FALSE)
      return FALSE;

    return TRUE;
  }


  /**
   * Call action perform method
   *
   * @param string $action_name name of the action
   * @param array  &$params     optional action parameters
   *
   * @return bool continue action process ?
   */
  protected function _do_action_perform($action_name, array & $params = NULL)
  {
    if ($this->_call_action($action_name, 'main', $this->_get_method_name(), $params) === FALSE)
      return FALSE;

    return TRUE;
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
   * Returns the action's instance if configured
   *
   * @param string $name name of the action
   *
   * @return Dispatcher_Action|FALSE
   */
  protected function _get_action_instance($name)
  {
    if (isset($this->_action_instances[$name]))
      return $this->_action_instances[$name];

    // Instanciate
    if (isset($this->_action_classnames[$name]))
    {
      $this->_instanciate_action($name);
    }
    else
    {
      $this->_action_instances[$name] = FALSE;
    }
    return $this->_get_action_instance($name);
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
   * @param Dispatcher::METHOD_* $method optional method
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
      case self::METHOD_GET :
        return 'get';
      case self::METHOD_POST :
        return 'post';
      case self::METHOD_AJAX_GET :
        return 'ajax_get';
      case self::METHOD_AJAX_POST :
        return 'ajax_post';
    }

    return 'unknown';
  }


  /**
   * Instanciates the action's instance
   *
   * @param string $name name of the action
   *
   * @return null
   *
   * @throws Kohana_Exception Can't perform dispatcher's action:
   *                          class :classname: does not exist
   */
  protected function _instanciate_action($name)
  {
    if ( ! class_exists($this->_action_classnames[$name]))
    {
      throw new Kohana_Exception(
        'Can\'t perform dispatcher\'s action: '.
        'class :classname: does not exist',
        array(':classname' => $this->_action_classnames[$name])
      );
    }

    $this->_action_instances[$name] = new $this->_action_classnames[$name];

    $this->_action_instances[$name]->controller = $this;
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

} // End class Dispatcher_Core