<?php

namespace App;

/**
 * Loads custom dependencies
 * - classes
 * - traits
 * - hooks
 */

/**
 * Helpers
 * - traits
 */
foreach (glob(__DIR__ . '/Traits/helpers/*.php') as $file) require_once $file;

/**
 * Hooks
 */
foreach (glob(__DIR__ . '/Traits/filters/*.php') as $file) require_once $file;

/**
 * Blocks
 */
foreach (glob(__DIR__ . '/Traits/blocks/*.php') as $file) require_once $file;

/**
 * Classes
 */
foreach (glob(__DIR__ . '/Classes/*.php') as $file) require_once $file;
