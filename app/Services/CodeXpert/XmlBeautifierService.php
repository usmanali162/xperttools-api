<?php

namespace App\Services\CodeXpert;

use App\Services\BaseToolService;
use DOMDocument;
use DOMException;
use SimpleXMLElement;
use Exception;

class XmlBeautifierService extends BaseToolService
{
    public function beautifyXml(
        string $input,
        int $indentSize = 2,
        bool $validateOnly = false,
        bool $removeComments = false,
        bool $sortAttributes = false
    ): array {
        $this->validateInput([
            'input' => $input,
            'indent_size' => $indentSize,
            'validate_only' => $validateOnly,
            'remove_comments' => $removeComments,
            'sort_attributes' => $sortAttributes,
        ], [
            'input' => 'required|string|max:5000000',
            'indent_size' => 'integer|min:0|max:8',
            'validate_only' => 'boolean',
            'remove_comments' => 'boolean',
            'sort_attributes' => 'boolean',
        ]);

        // Store original error handler
        $originalErrorHandler = set_error_handler(null);
        $errors = [];

        // Set custom error handler for XML parsing
        set_error_handler(function ($severity, $message, $file, $line) use (&$errors) {
            $errors[] = $this->parseXmlError($message);
            return true;
        });

        $validationResult = $this->validateXml($input, $errors);

        // Restore original error handler
        set_error_handler($originalErrorHandler);

        if (!$validationResult['valid']) {
            return [
                'valid' => false,
                'errors' => $validationResult['errors'],
                'formatted_xml' => null,
                'minified_xml' => null,
                'statistics' => $this->calculateStatistics($input, null),
            ];
        }

        if ($validateOnly) {
            return [
                'valid' => true,
                'errors' => [],
                'formatted_xml' => null,
                'minified_xml' => null,
                'statistics' => $this->calculateStatistics($input, $validationResult['dom']),
            ];
        }

        $dom = $validationResult['dom'];

        if ($removeComments) {
            $this->removeCommentsFromDom($dom);
        }

        if ($sortAttributes) {
            $this->sortAttributesInDom($dom);
        }

        // Format XML
        $dom->formatOutput = true;
        $formattedXml = $dom->saveXML();

        if ($indentSize !== 2) {
            $formattedXml = $this->adjustIndentation($formattedXml, $indentSize);
        }

        // Create minified version
        $domMinified = clone $dom;
        $domMinified->formatOutput = false;
        $domMinified->preserveWhiteSpace = false;
        $minifiedXml = $domMinified->saveXML();
        $minifiedXml = preg_replace('/>\s+</', '><', $minifiedXml);

        $this->logUsage('xml_beautifier', [
            'input_length' => mb_strlen($input),
            'indent_size' => $indentSize,
            'remove_comments' => $removeComments,
            'sort_attributes' => $sortAttributes,
            'validate_only' => $validateOnly,
        ]);

        return [
            'valid' => true,
            'errors' => [],
            'formatted_xml' => $formattedXml,
            'minified_xml' => $minifiedXml,
            'statistics' => $this->calculateStatistics($input, $dom),
            'options' => [
                'indent_size' => $indentSize,
                'remove_comments' => $removeComments,
                'sort_attributes' => $sortAttributes,
            ],
        ];
    }

    private function validateXml(string $xml, array &$errors): array
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        $loaded = $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);

        if (!$loaded) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $error) {
                $errors[] = $this->formatLibXmlError($error);
            }
            libxml_clear_errors();

            return [
                'valid' => false,
                'errors' => $errors,
                'dom' => null,
            ];
        }

        // Additional validation with SimpleXML for well-formedness
        try {
            $simpleXml = new SimpleXMLElement($xml);
        } catch (Exception $e) {
            $errors[] = [
                'message' => $e->getMessage(),
                'line' => null,
                'column' => null,
                'level' => 'error',
            ];

            return [
                'valid' => false,
                'errors' => $errors,
                'dom' => null,
            ];
        }

        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return [
            'valid' => true,
            'errors' => [],
            'dom' => $dom,
        ];
    }

    private function formatLibXmlError($error): array
    {
        $levels = [
            LIBXML_ERR_WARNING => 'warning',
            LIBXML_ERR_ERROR => 'error',
            LIBXML_ERR_FATAL => 'fatal',
        ];

        return [
            'message' => trim($error->message),
            'line' => $error->line,
            'column' => $error->column,
            'level' => $levels[$error->level] ?? 'error',
            'code' => $error->code,
        ];
    }

    private function parseXmlError(string $message): array
    {
        // Parse PHP XML error messages
        $pattern = '/on line (\d+)/';
        $line = null;
        
        if (preg_match($pattern, $message, $matches)) {
            $line = (int)$matches[1];
        }

        return [
            'message' => trim(preg_replace($pattern, '', $message)),
            'line' => $line,
            'column' => null,
            'level' => 'error',
        ];
    }

    private function removeCommentsFromDom(DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        $comments = $xpath->query('//comment()');

        foreach ($comments as $comment) {
            $comment->parentNode->removeChild($comment);
        }
    }

    private function sortAttributesInDom(DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@*]');

        foreach ($elements as $element) {
            if ($element->hasAttributes()) {
                $attributes = [];
                
                // Collect attributes
                foreach ($element->attributes as $attr) {
                    $attributes[$attr->nodeName] = $attr->nodeValue;
                }
                
                // Remove all attributes
                while ($element->hasAttributes()) {
                    $element->removeAttribute($element->attributes->item(0)->nodeName);
                }
                
                // Re-add in sorted order
                ksort($attributes);
                foreach ($attributes as $name => $value) {
                    $element->setAttribute($name, $value);
                }
            }
        }
    }

    private function adjustIndentation(string $xml, int $indentSize): string
    {
        if ($indentSize === 0) {
            // Remove all indentation
            $xml = preg_replace('/^\s+/m', '', $xml);
            $xml = preg_replace('/>\s+</', '><', $xml);
            return $xml;
        }

        $indent = str_repeat(' ', $indentSize);
        $lines = explode("\n", $xml);
        $result = [];

        foreach ($lines as $line) {
            if (preg_match('/^(\s*)(.+)$/', $line, $matches)) {
                $currentIndent = strlen($matches[1]);
                $level = $currentIndent / 2; // Default DOMDocument uses 2 spaces
                $newIndent = str_repeat($indent, (int)$level);
                $result[] = $newIndent . $matches[2];
            } else {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }

    private function calculateStatistics(string $input, ?DOMDocument $dom): array
    {
        $stats = [
            'input_size' => mb_strlen($input),
            'input_size_formatted' => $this->formatBytes(mb_strlen($input)),
        ];

        if ($dom !== null) {
            $xpath = new \DOMXPath($dom);
            
            $stats['total_elements'] = $xpath->query('//*')->length;
            $stats['total_attributes'] = $xpath->query('//@*')->length;
            $stats['total_comments'] = $xpath->query('//comment()')->length;
            $stats['total_text_nodes'] = $xpath->query('//text()[normalize-space()]')->length;
            $stats['depth'] = $this->calculateXmlDepth($dom->documentElement);
            $stats['namespaces'] = $this->extractNamespaces($dom);
            $stats['root_element'] = $dom->documentElement ? $dom->documentElement->tagName : null;
            
            // Calculate unique elements
            $elements = $xpath->query('//*');
            $uniqueElements = [];
            foreach ($elements as $element) {
                $uniqueElements[$element->tagName] = true;
            }
            $stats['unique_elements'] = count($uniqueElements);
        }

        return $stats;
    }

    private function calculateXmlDepth($node, int $currentDepth = 0): int
    {
        if (!$node || !$node->hasChildNodes()) {
            return $currentDepth;
        }

        $maxDepth = $currentDepth;
        
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $depth = $this->calculateXmlDepth($child, $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    private function extractNamespaces(DOMDocument $dom): array
    {
        $namespaces = [];
        $xpath = new \DOMXPath($dom);
        
        // Get all namespace declarations
        $nodes = $xpath->query('//*');
        foreach ($nodes as $node) {
            if ($node->namespaceURI && $node->prefix) {
                $namespaces[$node->prefix] = $node->namespaceURI;
            }
        }

        return $namespaces;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        
        $units = ['KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes / 1024;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getOptions(): array
    {
        return [
            'formatting_options' => [
                'indent_sizes' => [0, 2, 4, 8],
                'remove_comments' => [
                    'description' => 'Remove XML comments from output',
                    'default' => false,
                ],
                'sort_attributes' => [
                    'description' => 'Sort attributes alphabetically',
                    'default' => false,
                ],
                'validate_only' => [
                    'description' => 'Only validate XML without formatting',
                    'default' => false,
                ],
            ],
            'supported_features' => [
                'format' => 'Pretty print XML with customizable indentation',
                'minify' => 'Compress XML by removing whitespace',
                'validate' => 'Check if XML is well-formed with detailed error messages',
                'comments' => 'Remove comments from XML',
                'attributes' => 'Sort attributes alphabetically',
                'statistics' => 'Get XML structure statistics',
            ],
            'examples' => [
                'simple' => '<?xml version="1.0"?><root><item>Value</item></root>',
                'attributes' => '<?xml version="1.0"?><root id="1" name="test"><item type="example">Value</item></root>',
                'nested' => '<?xml version="1.0"?><root><parent><child>Value</child></parent></root>',
                'namespaces' => '<?xml version="1.0"?><root xmlns:ns="http://example.com"><ns:item>Value</ns:item></root>',
            ],
        ];
    }
}