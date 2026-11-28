<?php

namespace App\Ajax;

if (!class_exists(__NAMESPACE__.'\\Ajax')) {
    /**
     * Class Ajax
     *
     * @package App\Ajax
     */
    class Ajax
    {
        private static $instance;

        /**
         * Ajax constructor.
         */
        protected function __construct()
        {
            //
        }

        /**
         * Singleton
         *
         * @return Ajax
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    Ajax::instance();
}
