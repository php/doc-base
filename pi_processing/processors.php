<?php

function error_section_value_error_between(
    DOMDocument $dom,
    string $parameter,
    string $min,
    string $max
): DOMElement {
    $min_tag_name = is_numeric($min) ? 'literal' : 'constant';
    $max_tag_name = is_numeric($max) ? 'literal' : 'constant';

    $min_tag = '<' . $min_tag_name . '>' . $min . '</' . $min_tag_name . '>';
    $max_tag = '<' . $max_tag_name . '>' . $max . '</' . $max_tag_name . '>';

    $xml = str_replace(
        ['PARAMETER_NAME', 'MIN_TAG', 'MAX_TAG'],
        [$parameter, $min_tag, $max_tag],
        VALUE_ERROR_BETWEEN_ERROR_SECTION,
    );
    $localDom = new DOMDocument();
    $localDom->loadXML($xml);
    return $dom->importNode($localDom->documentElement, true);
}

function error_section_value_error_between_changelog(
    DOMDocument $dom,
    string $version,
    string $parameter,
    string $min,
    string $max
): DOMElement {
    $min_tag_name = is_numeric($min) ? 'literal' : 'constant';
    $max_tag_name = is_numeric($max) ? 'literal' : 'constant';

    $min_tag = '<' . $min_tag_name . '>' . $min . '</' . $min_tag_name . '>';
    $max_tag = '<' . $max_tag_name . '>' . $max . '</' . $max_tag_name . '>';

    $xml = str_replace(
        ['VERSION', 'PARAMETER_NAME', 'MIN_TAG', 'MAX_TAG'],
        [$version, $parameter, $min_tag, $max_tag],
        VALUE_ERROR_BETWEEN_CHANGELOG,
    );
    $localDom = new DOMDocument();
    $localDom->loadXML($xml);
    return $dom->importNode($localDom->documentElement, true);
}
