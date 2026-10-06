<?php

namespace AgentMedia\T3LocalCopy\T3Config\Parser;

final class FlexFormParser extends TcaLikeParserAbstract{
        
    /**
     * The DOMDocument representing the FlexForm XML structure.
     *
     * @var \DOMDocument
     */
    private \DOMDocument $flexDom;

    /**
     * Constructor for the FlexFormParser.
     *
     * @param \DOMDocument $flexDom The DOMDocument representing the FlexForm XML structure.
     */
    public function __construct(\DOMDocument $flexDom) {
        $this->flexDom = $flexDom;
    }

    /**
     * Creates a FlexFormParser instance from an XML string.
     *
     * @param string $flexXml The XML string representing the FlexForm configuration.
     * @return FlexFormParser Returns the created FlexFormParser instance.
     */
    public static function fromXml(string $flexXml): FlexFormParser {
        $dom = new \DOMDocument();
        if (!$dom->loadXML($flexXml)) {
            throw new \InvalidArgumentException("Failed to load FlexForm XML from string.");
        }
        return new self($dom);
    }

    /**
     * Creates a FlexFormParser instance from an XML file containing the FlexForm XML configuration.
     *
     * @param string $flexConfigPath The path to the XML file containing the FlexForm XML configuration.
     * @return FlexFormParser Returns the created FlexFormParser instance.
     * @throws \Exception Raises an exception if the XML file does not exist or is not readable.
     */
    public static function fromFile(string $flexConfigPath): FlexFormParser {
        if (!file_exists($flexConfigPath) || !is_readable($flexConfigPath)) {
            throw new \Exception("FlexForm XML configuration file not found or not readable: " . $flexConfigPath);
        }
        return self::fromXml(file_get_contents($flexConfigPath));
    }

    /**
     * Gets the unique DOM value node for the specified sheet and field.
     *
     * @param string $sheet The sheet name within the FlexForm XML structure.
     * @param string $field The field name within the specified sheet.
     * @return \DOMNode|null Returns the unique DOM value node if found, otherwise null.
     */
    public function getUniqueDomValueNode(string $sheet, string $field): ?\DOMNode {
        $xpath = new \DOMXPath($this->flexDom);
        $query = sprintf('//sheet[@index="%s"]//field[@index="%s"]/value', $sheet, $field);
        $nodes = $xpath->query($query);
        return $nodes->length > 0 ? $nodes->item(0) : null;
    }

    /**
     * Gets the value of the specified field within the specified sheet.
     *
     * @param string $sheet The sheet name within the FlexForm XML structure.
     * @param string $field The field name within the specified sheet.
     * @param string|null $default The default value to return if the field is not found.
     * @return string|null Returns the value of the field if found, otherwise the default value.
     */
    public function getValue(string $sheet, string $field, ?string $default = null): ?string {
        $node = $this->getUniqueDomValueNode($sheet, $field);
        if ($node === null) {
            return $default;
        }
        return $node->nodeValue ?? $default;
    }

    /**
     * Gets the configuration fields that match the specified criteria.
     *
     * @param array $matchConfig The criteria to match against the field configurations. All sheets are scanned for matching fields.
     * @return array Returns an array of matching configuration fields.
     */
    public function getConfigFields(array $matchConfig): array {
        $result = [];
        $xpath = new \DOMXPath($this->flexDom);
        $allSheets = $xpath->query('/T3DataStructure/sheets/*');
        foreach ($allSheets as $sheetNode) {
            /**
             * @var \DOMElement $sheetNode
             */
            $sheetName = $sheetNode->nodeName;
            $fields = $xpath->query('.//el/*', $sheetNode);
            foreach ($fields as $fieldNode) {
                /**
                 * @var \DOMElement $fieldNode
                 */
                $fieldName = $fieldNode->nodeName;
                $configNode = $xpath->query('.//config', $fieldNode)->item(0);
                $fieldDef = [
                    'config' => $configNode ? $this->nodeToArray($configNode) : []
                ];
                if ($this->checkMatch($fieldDef, $matchConfig)) {
                    $result[$sheetName][$fieldName] = $fieldDef;
                }
            }
        }
        return $result;
    }

    /**
     * Converts a DOMNode to an associative array.
     *
     * @param \DOMNode $node The DOMNode to convert.
     * @return array|string The converted array or string if it's a leaf node.
     */
   private function nodeToArray(\DOMNode $node): array|string
    {
        $children = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $children[] = $child;
            }
        }
        // Leaf node: return its text value
        if (count($children) === 0) {
            return trim($node->textContent);
        }

        $result = [];

        foreach ($children as $child) {
            $name  = $child->nodeName;
            $value = $this->nodeToArray($child);

            // Preserve repeated element names as arrays
            if (array_key_exists($name, $result)) {
                if (!is_array($result[$name]) ||
                    !array_is_list($result[$name])) {
                    $result[$name] = [$result[$name]];
                }
                $result[$name][] = $value;
            } else {
                $result[$name] = $value;
            }
        }
        return $result;
    }


}