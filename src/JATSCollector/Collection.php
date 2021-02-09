<?php


namespace JATSCollector;


use DOMDocument;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Class Collection
 * @package JATSCollector
 */
class Collection
{
    /**
     * @var Logger|null
     */
    protected $logger = null;
    protected $articles = [];
    /**
     * @var array
     */
    protected $sourceFiles = [];
    protected $inputPath = '';
    protected $outputPath = '';
    /**
     * @var DOMDocument
     */
    private $dom;

    /**
     * Collection constructor.
     * @param $inputPath
     * @param $outputPath
     */
    public function __construct($inputPath, $outputPath)
    {
        $this->dom      = new DOMDocument('1.0', 'UTF-8');
        $this->articles = $this->dom->createElement('articles');
        $this->dom->appendChild($this->articles);
        $this->inputPath  = $inputPath;
        $this->outputPath = $outputPath;
        $this->setupLogger();
        $this->collectSourceFiles();
        $this->parseSourceFiles();
        $this->dom->formatOutput = true;
        file_put_contents($this->outputPath . '/output.xml', $this->dom->saveXML());
    }

    protected function setupLogger(): void
    {
        $this->logger = new Logger('name');
        @unlink($this->outputPath . '/warning.log');
        @unlink($this->outputPath . '/debug.log');
        $this->logger->pushHandler(new StreamHandler($this->outputPath . '/warning.log', Logger::WARNING));
        $this->logger->pushHandler(new StreamHandler($this->outputPath . '/debug.log', Logger::DEBUG));
    }

    protected function collectSourceFiles(): void
    {
        $this->sourceFiles = [];

        $recursiveIteratorIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->inputPath,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /**
         * @var SplFileInfo[] $recursiveIteratorIterator
         */
        foreach ($recursiveIteratorIterator as $splFileInfo) {
            if ($splFileInfo->getExtension() === 'xml') {
                $this->sourceFiles[] = $splFileInfo->getRealPath();
            }
        }
        $this->logger->debug('Sourcefiles collected');
    }

    protected function parseSourceFiles()
    {
//        $maxK = 49;
        $maxK = null;
        foreach ($this->sourceFiles as $k => $sourceFile) {
            $this->logger->debug('Starting parse on: ', [$sourceFile]);
            $article = new Article($sourceFile, $this->dom);
            $this->articles->appendChild($article->getXmlElement());
            if ($k === $maxK) {
                break;
            }
        }
    }
}
