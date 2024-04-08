<?php
/**
 * @file
 * UI API class file.
 */

namespace Smp\Ui;

/**
 * This is... really? What is this?
 * 
 * There's nothing to translate into.
 */
interface UiApi {
  /**
   * Information coming from the UI.
   * 
   * Usually it's either text or some events from a GUI.
   * 
   * @param mixed $input
   */
  function input(mixed $input) : void;
  
  /**
   * Game output.
   * 
   * The game will output some data that the UI system
   * will translate to something.
   * 
   * @return mixed
   */
  function output() : mixed;
}
