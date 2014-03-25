<?php

namespace Webforge\Doctrine\Compiler;

class TestReflection {

  public static function entityNames() {
    return array(
      array('ACME\Blog\Entities\User', 'User'),
      array('ACME\Blog\Entities\Author', 'Author'),
      array('ACME\Blog\Entities\Post', 'Post'),
      array('ACME\Blog\Entities\Category', 'Category'),
      array('ACME\Blog\Entities\Tag', 'Tag'),

      array('ACME\Blog\Entities\Page', 'Page'),

      array('ACME\Blog\Entities\ContentStream\Entry', 'ContentStream/Entry'),
      array('ACME\Blog\Entities\ContentStream\TextBlock', 'ContentStream/TextBlock'),
      array('ACME\Blog\Entities\ContentStream\Paragraph', 'ContentStream/Paragraph'),
      array('ACME\Blog\Entities\ContentStream\Stream', 'ContentStream/Stream')
    );
  }

  public static function entitySlugs() {
    return array(
      array('ACME\Blog\Entities\User', 'user', 'users'),
      array('ACME\Blog\Entities\Author', 'author', 'authors'),
      array('ACME\Blog\Entities\Post', 'post', 'posts'),
      array('ACME\Blog\Entities\Category', 'category', 'categories'),
      array('ACME\Blog\Entities\Tag', 'tag', 'tags'),
      array('ACME\Blog\Entities\Page', 'page', 'pages'),

      array('ACME\Blog\Entities\ContentStream\Entry', 'content-stream_entry', 'content-stream_entries'),
      array('ACME\Blog\Entities\ContentStream\Paragraph', 'content-stream_paragraph', 'content-stream_paragraphs'),
      array('ACME\Blog\Entities\ContentStream\TextBlock', 'content-stream_text-block', 'content-stream_text-blocks'),
      array('ACME\Blog\Entities\ContentStream\Stream', 'content-stream_stream', 'content-stream_streams')
    );
  }

  public static function flatEntityFQNs() {
    $expectedFQNs = array();
    foreach (TestReflection::entityNames() as $list) {
      $expectedFQNs[] = $list[0];
    }

    return $expectedFQNs;
  }

  public static function tableNames() {
    return array(
      array('users'),
      array('authors'),
      array('categories'),
      array('posts'),
      array('posts2categories'),
      array('tags'),
      array('pages')
    );
  }
}
