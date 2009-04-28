<?php

function xml_to_result($dom) {
    $root = $dom->firstChild;
    foreach($root->attributes as $attr) $res[$attr->name] = $attr->value;
    $node = $root->firstChild;
    $i = 0;
    while($node) {
        switch($node->nodeName) {
            case 'name':
                $subnode = $node->firstChild;
                while($subnode) {
                    $subnodes = $subnode->childNodes;
                    foreach($subnodes as $n) {
                        if($n->hasChildNodes()) {
                            foreach($n->childNodes as $cn) $res[$i][$subnode->nodeName][$n->nodeName]=trim($cn->nodeValue);
                        } else $res[$i][$subnode->nodeName]=trim($n->nodeValue);
                    }
                    $subnode = $subnode->nextSibling;
                }
                break;
            default:
                $res[$node->nodeName] = trim($node->nodeValue);
                $i--;
                break;
        }
        $i++;
        $node = $node->nextSibling;
    }
    return $res;
}

?>