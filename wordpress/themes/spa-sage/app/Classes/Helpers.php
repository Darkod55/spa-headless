<?php

namespace App\Helpers;

use App\Helpers\Traits\icons;
use App\Helpers\Traits\media;
use App\Helpers\Traits\strings;

if (!class_exists(__NAMESPACE__.'\\Helpers')) {
    /**
     * Class Helpers
     *
     * @package App\Helpers
     */
    class Helpers
    {
        private static $instance;

        use icons;
        use media;
        use strings;

        /**
         * Helpers constructor.
         */
        protected function __construct()
        {
            //
        }

        /**
         * Singleton
         *
         * @return Helpers
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    Helpers::instance();
}
