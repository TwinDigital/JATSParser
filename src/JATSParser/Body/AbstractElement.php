<?php

namespace JATSParser\Body;

use DOMElement;
use JATSParser\Body\Document as Document;

/**
 * Class AbstractElement
 * @package JATSParser\Body
 */
abstract class AbstractElement implements JATSElement
{

    protected const JATS_EXTRACT_CAPTION = 1;
    protected const JATS_EXTRACT_TITLE   = 2;

    protected $xpath;

    protected function __construct(DOMElement $domElement)
    {
        $this->xpath = Document::getXpath();
    }

    protected function extractFromElement(string $xpathExpression, DOMElement $domElement = null): ?string
    {
        $nodeTextValue = null;
        $domElement !== null ? $searchNodes = $this->xpath->evaluate(
            $xpathExpression,
            $domElement
        ) : $searchNodes = $this->xpath->evaluate($xpathExpression);
        if (!$searchNodes || !is_object($searchNodes) || !property_exists($searchNodes, 'length')) {
            return null;
        }
        if ($searchNodes->length > 0) {
            foreach ($searchNodes as $searchNode) {
                $nodeTextValue = $searchNode->nodeValue;
            }
        }

        return $nodeTextValue;
    }

    protected function extractFromElements(string $xpathExpression, DOMElement $domElement = null): ?array
    {
        $nodeTextValues = array();
        $domElement !== null ? $searchNodes = $this->xpath->evaluate(
            $xpathExpression,
            $domElement
        ) : $searchNodes = $this->xpath->evaluate($xpathExpression);
        if ($searchNodes->length > 0) {
            foreach ($searchNodes as $searchNode) {
                $nodeTextValues[] = $searchNode->nodeValue;
            }
        }

        return $nodeTextValues;
    }

    protected function extractFormattedText(string $xpathExpression, DOMElement $domElement = null): array
    {
        $nodeTextValues  = array();
        $xpathExpression .= "//text()";
        $domElement !== null ? $searchNodes = $this->xpath->evaluate(
            $xpathExpression,
            $domElement
        ) : $searchNodes = $this->xpath->evaluate($xpathExpression);
        if ($searchNodes->length > 0) {
            foreach ($searchNodes as $searchNode) {
                $jatsText         = new Text($searchNode);
                $nodeTextValues[] = $jatsText;
            }
        }

        return $nodeTextValues;
    }

    /**
     * @param DOMElement $element
     * @param $extractType
     * @return array|null
     */
    protected function extractTitleOrCaption(DOMElement $element, $extractType): ?array
    {
        $titleOrCaption = array();
        $captionNodes   = $this->xpath->query(".//caption", $element);
        foreach ($captionNodes as $captionNode) {
            if ($extractType === self::JATS_EXTRACT_TITLE) {
                $titleElements = $this->xpath->query(".//title//text()", $captionNode);
                if ($titleElements->length > 0) {
                    foreach ($titleElements as $titleElement) {
                        $jatsText         = new Text($titleElement);
                        $titleOrCaption[] = $jatsText;
                    }
                }
            } elseif ($extractType === self::JATS_EXTRACT_CAPTION) {
                $captionParagraphs = $this->xpath->query(".//p", $captionNode);
                foreach ($captionParagraphs as $captionParagraph) {
                    $par              = new Par($captionParagraph);
                    $titleOrCaption[] = $par;
                }
            }
        }

        return $titleOrCaption;
    }

    /**
     * @return string[]
     */
    public static function mappedBlockElements(): array
    {
        return ["Figure" => "fig", "Table" => "table-wrap", "Listing" => "list"];
    }
}
