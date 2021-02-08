<?php

namespace JATSParser\HTML;

use DOMElement;
use JATSParser\Body\Verse as JATSVerse;
use JATSParser\HTML\Text as HTMLText;

/**
 * Class Verse
 * @package JATSParser\HTML
 */
class Verse extends DOMElement
{

    public function __construct()
    {
        parent::__construct("p");
    }

    /**
     * @param JATSVerse $jatsVerse
     * @return void
     */
    public function setContent(JATSVerse $jatsVerse)
    {
        if (!empty($jatsVerse->getContent())) {
            foreach ($jatsVerse->getContent() as $item) {
                foreach ($item as $text) {
                    HTMLText::extractText($text, $this);
                }
                $this->appendChild($this->ownerDocument->createElement("br"));
            }
        }

        if (!empty($attribTexts = $jatsVerse->getAttrib())) {
            $citeElement = $this->ownerDocument->createElement("cite");
            $this->appendChild($citeElement);
            foreach ($attribTexts as $attribText) {
                Text::extractText($attribText, $citeElement);
            }
        }
    }
}
