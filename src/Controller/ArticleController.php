<?php

namespace Drupal\article_xml\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\image\Entity\ImageStyle;

class ArticleController extends ControllerBase {
  /**
   * Function (Public): Returns content to my route.
   * See this in action at /xml/articles.xml
   */
  public function article_xml() {
    $response = new Response();
    $response->headers->set('Content-Type', 'text/xml');
    // Setup general nid query.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'article');
    $nids = array_values($query->execute());
    foreach ($nids as $key => $value) {
      $result[$key] = self::xmlOutput($value);
    }

    // Prepend XML wrapper.
    array_unshift($result, '<?xml version="1.0" encoding="utf-8"?><entities title="Articles">');
    // Append XML closure.
    $result[] = '</entities>';
    $response->setContent(implode('', $result));
    return $response;
  }

  public function xmlOutput($input) {
    //Get the article node
    $node = Node::load($input);

    //Get article node ID
    $nid = $node->id();

    //Get article title
    $title = $node->getTitle();

    //Get article body
    $body = $node->get('body')->value;

    ///Get article image
    $imageURL = '';
    $getImage = $node->get('field_image');
    if ($getImage->entity != null) {
      $imageEntity = $getImage->entity->getFileURI();
      $imageURL =  ImageStyle::load('large')->buildUrl($imageEntity);
      $imageURL = preg_replace("/(.*)(.jpg)(.*)/", "$1$2", $imageURL);
    }

    //Get article tags
    $tags = $node->get('field_tags');
    $tagKeys = array_keys($tags->getValue());
    $lastTag = array_pop($tagKeys);
    $tagsOutput = [];
    foreach ($tags as $key => $value) {
      $tagID = $value->target_id;

      //load the term and get the term name
      $tagName = Term::load($tagID)->name->value;

      //add comma and space to each term unless it's the final term
      if ($key != $lastTag) {
        $tagName = $tagName . ', ';
      }

      //populate empty tags array with loop output
      array_push($tagsOutput, $tagName);
    }

    //compile our XML markup with values received above
    $compiled = [
      '<article  whereValue="' . $nid . '">',
      '<title>' . $title . '</title>',
      '<body><![CDATA[' . $body . ']]></body>',
      '<image>' . $imageURL . '</image>',
      '<tags>' . implode($tagsOutput) . '</tags>',
      '</article>',
    ];

    //return the XML markup
    return implode('', $compiled);
  }
}