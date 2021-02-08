<?php

namespace JATSParser\HTML;

use DOMElement;
use JATSParser\Body\Par as JATSPar;
use JATSParser\Body\Text as JATSText;
use JATSParser\HTML\Text as HTMLText;

/**
 * Class Par
 * @package JATSParser\HTML
 */
class Par extends DOMElement
{

    /**
     * Par constructor.
     * @param null $nodeName
     */
    public function __construct($nodeName = null)
    {
        $nodeName === null ? parent::__construct("p") : parent::__construct($nodeName);
    }

    /**
     * @param JATSPar $jatsPar
     * @return void
     */
    public function setContent(JATSPar $jatsPar)
    {
        foreach ($jatsPar->getContent() as $jatsText) {
            HTMLText::extractText($jatsText, $this);
        }
    }
}
