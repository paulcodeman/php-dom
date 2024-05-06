<?php
class D {
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

    private function cssToXPath(string $selector): string {
        return $this->xpathCache[$selector] ??= new SelectorToXPATH($selector);
    }

    public function querySelectorAll(string $selector, DOMNode $element = null) {
        $xpathQuery = self::cssToXPath($selector);
        $elements = $this->xpath->query($xpathQuery, $element);
        if ($elements === false) {
            throw new Exception('Ошибка при выполнении XPath запроса');
        }

        foreach ($elements as $element) {
            yield new DomItem($element, $this);
        }
    }

    public function querySelector(string $selector, DOMNode $element = null)
    {
        $items = $this->DOM->querySelectorAll($selector, $element);
        foreach ($items as $item) {
            return $item;
        }

        return null;
    }

    public static function setOuterHTML(DOMNode $element, string $html)
    {
        $newElement = $element->ownerDocument->createDocumentFragment();
        $appendResult = @$newElement->appendXML($html);
        if (!$appendResult) {
            // Обработка ошибки, если HTML не корректен
            throw new Exception('Ошибка при добавлении HTML во фрагмент');
        }
        // Проверка на существование родительского узла
        if ($element->parentNode !== null) {
            $element->parentNode->replaceChild($newElement, $element);
        } else {
            throw new Exception('Элемент не имеет родительского узла');
        }
    }

    public static function getOuterHTML(DOMElement $element): string
    {
        // Проверка на существование родительского узла
        if ($element->parentNode !== null) {
            return $element->ownerDocument->saveHTML($element);
        }
        return '';
    }

    public static function removeChild(DOMElement $element)
    {
        $element->parentNode->removeChild($element);
    }

    public static function prepand($parentElement, $element)
    {
        if ($element === null) {
            throw new InvalidArgumentException("Element cannot be null.");
        }

        if ($parentElement === null) {
            throw new InvalidArgumentException("Parent element cannot be null.");
        }

        $firstChild = $parentElement->firstChild;

        if ($firstChild !== null) {
            $parentElement->insertBefore($element, $firstChild);
        } else {
            $parentElement->appendChild($element);
        }
    }


    public function getHTML(): string
    {
        $html = $this->dom->saveHTML();
        $html = html_entity_decode(trim($html));
        return $html;
    }


}

class DomItem {
    function __construct(private $DOM_ITEM, private $DOM) {}

    public function __get($property)
    {
        switch ($property)
        {
            case 'getInnerHTML':
                return $this->{$property}();
            default:
                throw new Exception("Property {$property} is not accessible.");
        }
    }

    public function __set($property, $value)
    {
        switch ($property)
        {
            case 'setInnerHTML':
                $this->{$property}($value);
                break;
            default:
                throw new Exception("Property {$property} is not accessible or read-only.");
        }
    }

    public function setInnerHTML(string $html) {
        // Создаем фрагмент документа для добавления HTML
        $fragment = $this->DOM_ITEM->ownerDocument->createDocumentFragment();

        // Очищаем текущее содержимое элемента
        while ($this->DOM_ITEM->hasChildNodes()) {
            $this->DOM_ITEM->removeChild($this->DOM_ITEM->firstChild);
        }

        // Пытаемся добавить HTML в фрагмент документа
        $appendResult = $fragment->appendXML($html);
        if ($appendResult) {
            if (!empty($fragment->textContent)) {
                $this->DOM_ITEM->appendChild($fragment);
            }
        } else {
            $this->DOM_ITEM->textContent = $html;
        }
    }

    public function getInnerHTML(): string
    {
        $innerHTML = '';
        foreach ($this->DOM_ITEM->childNodes as $child) {
            $innerHTML .= $this->DOM_ITEM->ownerDocument->saveHTML($child);
        }
        return $innerHTML;
    }

    public function querySelectorAll(string $selector)
    {
        return $this->DOM->querySelectorAll($selector, $this->DOM_ITEM);
    }

    public function querySelector(string $selector)
    {
        return $this->DOM->querySelector($selector, $this->DOM_ITEM);
    }
}
