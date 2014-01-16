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

      array('ACME\Blog\Entities\ContentStream\Paragraph', 'ContentStream/Paragraph'),
      array('ACME\Blog\Entities\ContentStream\Stream', 'ContentStream/Stream')
    );
  }

  public static function tableNames() {
    return array(
      array('users'),
      array('authors'),
      array('categories'),
      array('posts'),
      array('posts2categories'),
      array('tags')
    );
  }
}
