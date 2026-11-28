<?php

namespace App;

if (!class_exists(__NAMESPACE__.'\\Inits')) {
    /**
     * Class Inits
     *
     * A collection of class initiators
     *
     * @package App
     */
    class Inits
    {
        private static $instance;

        /**
         * Inits constructor.
         */
        protected function __construct()
        {
            //
        }

        /**
         * Singleton
         *
         * @return Inits
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    Inits::instance();
}

if (!function_exists('App\\helpers')) {
    /**
     * @return Helpers\Helpers
     */
    function helpers()
    {
        return \App\Helpers\Helpers::instance();
    }
}
