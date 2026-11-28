<?php

namespace App\MpxBlocks;

use App\MpxBlocks\Traits\blocks;

if (!class_exists(__NAMESPACE__.'\\MpxBlocks')) {
    /**
     * Class MpxBlocks
     *
     * @package App\MpxBlocks
     */
    class MpxBlocks
    {
        private static $instance;

        use blocks;

        /**
         * MpxBlocks constructor.
         */
        protected function __construct()
        {
            $this->addBlocksCategory();
            $this->loadingBlockAssets();
            $this->createPatternCategories();
            $this->renderBladeBlockTemplates();
            $this->mpxBlockstudioPath();
            $this->disableFrontendBlockstudioTailwind();
            $this->bridgeTailwindConfig();
            $this->mpxRemoveInnerBlocksWrapper();
            $this->unwrapPartialButton();
            $this->duplicatePartnersSliderGallery();
            $this->removeIsStyleClassFromMpxBlocks();
            $this->moveCoreButtonClassesToLink();
            $this->populateTextColours();
            $this->populateBGColours();
            $this->populateSpriteIcons();
            $this->populateButtonTypes();
            $this->populateVideoType();
            $this->populateGradientBGColours();
            $this->populateColumnPosition();
            $this->populateRowAlignment();
            $this->populateColumnGap();
            $this->populateRowLayout();
            $this->populateSectionPadding();
            $this->populateInnerPadding();
            $this->imageRatios();
            $this->imageTypes();
            $this->imagePositions();
            $this->faqWidget();
            $this->addBodyClassEditor();
        }

        /**
         * Singleton
         *
         * @return MpxBlocks
         */
        public static function instance()
        {
            if (! (self::$instance instanceof self)) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }

    MpxBlocks::instance();
}
