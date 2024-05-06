<?php
class DOM {
    private $xpathCache = [];
    private $xpath;
    private $dom;

    public function __construct(string $html) {
        $this->dom = new DOMDocument('1.0', 'utf-8');
        libxml_use_internal_errors(true);
        if (!$this->dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR)) {
            throw new Exception('Ошибка при загрузке HTML');
        }
        $this->xpath = new DOMXPath($this->dom);
    }

    private function cssToXPath($selector): string {
        return $this->xpathCache[$selector] ??= new SelectorToXPATH($selector);
    }

    private function querySelectorAllHelper($xpathQuery, $element = null): array {
        $elements = $this->xpath->query($xpathQuery, $element);
        return $elements ? iterator_to_array($elements) : [];
    }

    public function querySelectorAll($selector, $element = null): array {
        $xpathQuery = $this->cssToXPath($selector);

        return $this->querySelectorAllHelper($xpathQuery, $element);
    }
}
