<?php
/**
 * Declares Dispatcher_Core_Action
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
 * @link      https://github.com/emtou/kohana-dispatcher/tree/master/classes/dispatcher/core/action.php
 */

defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Provides Dispatcher_Core_Action
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
 * @link      https://github.com/emtou/kohana-dispatcher/tree/master/classes/dispatcher/core/action.php
 */
abstract class Dispatcher_Core_Action
{
  public $controller = NULL; /** Controller instance calling this action */


  /**
   * Perform action's phase for given method
   *
   * @param string $phase   action's phase to perform
   * @param string $method  action's calling method
   * @param array  &$params optional action parameters
   *
   * @return bool continue action process ?
   */
  public function perform($phase, $method, array & $params = NULL)
  {
    $method_name = '_'.$phase.'_'.$method;
    if ( ! method_exists($this, $method_name))
    {
      return TRUE;
    }

    return $this->{$method_name}($params);
  }
} // end class Dispatcher_Core_Action