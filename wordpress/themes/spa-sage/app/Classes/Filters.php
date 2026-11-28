<?php

namespace App\Filters;

use App\Filters\Traits\navs;
use App\Filters\Traits\seo;
use App\Filters\Traits\strings;

if (!class_exists(__NAMESPACE__.'\\Filters')) {
    /**
     * Class Filters
     *
     * @package App\Filters
     */
    class Filters
    {
        private static $instance;

        use navs;
        use seo;
        use strings;

        /**
         * Filters constructor.
         */
        protected function __construct()
        {
            /**
             * Nav filters
             * - uses Trait\navs
             */
            $this->navFilters();

            /**
             * Disabling comments and pingbacks
             * - uses Trait\strings
             */
//            $this->disableCommentsAndPingbacks();
        }

        /**
         * Singleton
         *
         * @return Filters
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    Filters::instance();
}
