<?php

namespace JATSParser\Body;

use DOMElement;
use JATSParser\Body\Row as Row;

/**
 * Class Table
 * @package JATSParser\Body
 */
class Table extends AbstractElement
{

    /* @var $id string */
    private $id;

    /* @var $label string */
    private $label;

    /* @var $content array */
    private $content;

    /* @var $title array */
    private $title;

    /* @var $notes array */
    private $notes;

    /* @var $link array */
    private $link;

    public function __construct(DOMElement $tableWraper)
    {
        parent::__construct($tableWraper);

        $this->label = $this->extractFromElement(".//label", $tableWraper);
        $this->link  = $this->extractFromElement("./@xlink:href", $tableWraper);
        $this->id    = $this->extractFromElement("./@id", $tableWraper);
        $this->title = $this->extractTitleOrCaption($tableWraper, self::JATS_EXTRACT_TITLE);
        $this->notes = $this->extractTitleOrCaption($tableWraper, self::JATS_EXTRACT_CAPTION);

        $this->extractContent($tableWraper);
    }

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getTitle(): ?array
    {
        return $this->title;
    }

    public function getNotes(): ?array
    {
        return $this->notes;
    }


    /**
     * @param DOMElement $tableWraper
     * @return void
     */
    private function extractContent(DOMElement $tableWraper)
    {
        $content = array();

        $tableHeadNode = $this->xpath->query(".//thead", $tableWraper);
        if ($tableHeadNode->length > 0) {
            $hasHead = true;
        } else {
            $hasHead = false;
        }

        $tableBodyNode = $this->xpath->query(".//tbody", $tableWraper);
        if ($tableBodyNode->length > 0) {
            $hasBody = true;
        } else {
            $hasBody = false;
        }

        $rowNodes = $this->xpath->query(".//tr", $tableWraper);
        foreach ($rowNodes as $rowNode) {
            $row       = new Row($rowNode);
            $content[] = $row;
        }
        $this->content = $content;
    }
}
