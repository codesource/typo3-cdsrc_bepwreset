<?php
/**
 * @copyright Copyright (c) 2016 Code-Source
 */
namespace CDSRC\CdsrcBepwreset\View;


use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class is mainly used because TYPO3 7.6 do not initialize and allow rendering of single partial.
 * This has been fixed in TYPO3 8.x
 *
 * @package CDSRC\CdsrcBepwreset\View
 */
class MailStandaloneView extends StandaloneView
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ContentObjectRenderer $contentObject = null)
    {
        parent::__construct();
        if(!$this->baseRenderingContext->getControllerContext()){
            $this->baseRenderingContext->setControllerContext($this->controllerContext);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getCurrentRenderingContext(){
       return parent::getCurrentRenderingContext() ?: $this->baseRenderingContext;
   }
}